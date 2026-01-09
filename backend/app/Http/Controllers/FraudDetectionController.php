<?php

namespace App\Http\Controllers;

use App\Models\PointOfSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class FraudDetectionController extends Controller
{
    /**
     * Detect fraud patterns and return flagged transactions/PDVs
     */
    public function detect(Request $request)
    {
        $scope = $request->input('scope', 'global'); // global, dealer, pdv
        $entityId = $request->input('entity_id');
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $limit = $request->input('limit', 30); // Limite par type de fraude
        $offset = $request->input('offset', 0); // Pour pagination

        // Get cache settings from system settings
        $cacheEnabled = \App\Models\SystemSetting::getValue('cache_fraud_detection_enabled', true);
        $cacheTtl = \App\Models\SystemSetting::getValue('cache_fraud_detection_ttl', 180); // minutes

        $cacheKey = "fraud_detection_{$scope}_{$entityId}_{$startDate}_{$endDate}_{$limit}_{$offset}";

        // If cache is disabled, bypass it
        if (!$cacheEnabled) {
            return $this->executeFraudDetection($scope, $entityId, $startDate, $endDate, $limit, $offset);
        }

        return Cache::tags(['fraud-detection'])->remember($cacheKey, $cacheTtl * 60, function () use ($scope, $entityId, $startDate, $endDate, $limit, $offset) {
            return $this->executeFraudDetection($scope, $entityId, $startDate, $endDate, $limit, $offset);
        });
    }

    /**
     * Execute fraud detection logic
     */
    private function executeFraudDetection($scope, $entityId, $startDate, $endDate, $limit, $offset)
    {
            $alerts = [];

            // 1. Split deposit fraud - Main fraud pattern (high depot count vs retrait)
            $alerts = array_merge($alerts, $this->detectSplitDepositFraud($scope, $entityId, $startDate, $endDate, $limit, $offset));

            // 2. Off-hours large transactions (>500k FCFA outside 8am-8pm)
            $alerts = array_merge($alerts, $this->detectOffHoursLargeTransactions($scope, $entityId, $startDate, $endDate, $limit, $offset));

            // 3. Sudden activity spikes (>3x average daily volume)
            $alerts = array_merge($alerts, $this->detectActivitySpikes($scope, $entityId, $startDate, $endDate, $limit, $offset));

            // 4. PDV earning more commission than generating CA (commission fraud)
            $alerts = array_merge($alerts, $this->detectCommissionOverCa($scope, $entityId, $startDate, $endDate, $limit, $offset));

            // Calculate risk scores
            foreach ($alerts as &$alert) {
                $alert['risk_score'] = $this->calculateRiskScore($alert);
                $alert['risk_level'] = $this->getRiskLevel($alert['risk_score']);
            }

            // Sort by risk score descending
            usort($alerts, function ($a, $b) {
                return $b['risk_score'] <=> $a['risk_score'];
            });

            $summary = [
                'total_alerts' => count($alerts),
                'high_risk' => count(array_filter($alerts, fn($a) => $a['risk_level'] === 'high')),
                'medium_risk' => count(array_filter($alerts, fn($a) => $a['risk_level'] === 'medium')),
                'low_risk' => count(array_filter($alerts, fn($a) => $a['risk_level'] === 'low')),
                'total_flagged_amount' => array_sum(array_column($alerts, 'flagged_amount')),
            ];

            return response()->json([
                'summary' => $summary,
                'alerts' => $alerts,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'count' => count($alerts),
                    'has_more' => count($alerts) >= ($limit * 4), // Approximation si on a le max possible
                ],
                'generated_at' => now()->toDateTimeString(),
            ]);
    }

    /**
     * Detect split deposit fraud - PDV multiplying deposits to gain more commissions
     * Key indicators: High depot count relative to retrait count
     */
    private function detectSplitDepositFraud($scope, $entityId, $startDate, $endDate, $limit = 30, $offset = 0)
    {
        $alerts = [];
        $checkedCount = 0;
        $matchedCount = 0;

        $pdvQuery = PointOfSale::where('status', 'validated');
        if ($scope === 'dealer' && $entityId) {
            $pdvQuery->where('organization_id', $entityId);
        } elseif ($scope === 'pdv' && $entityId) {
            $pdvQuery->where('id', $entityId);
        }
        // Récupérer TOUS les PDV validés pour détecter les fraudes
        $pdvs = $pdvQuery->with('organization')->get();

        Log::info("Split Deposit Detection", [
            'scope' => $scope,
            'entity_id' => $entityId,
            'pdv_count' => $pdvs->count(),
            'date_range' => "$startDate to $endDate"
        ]);

        foreach ($pdvs as $pdv) {
            $stats = DB::table('pdv_transactions')
                ->where('pdv_numero', $pdv->numero_flooz)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->selectRaw('
                    SUM(count_depot) as total_depot,
                    SUM(count_retrait) as total_retrait,
                    SUM(sum_depot) as total_depot_amount,
                    AVG(sum_depot / NULLIF(count_depot, 0)) as avg_depot_amount,
                    COUNT(DISTINCT transaction_date) as active_days
                ')
                ->first();

            if (!$stats) {
                continue;
            }

            $totalOperations = $stats->total_depot + $stats->total_retrait;
            
            // Condition: au moins 50 opérations, 80% de dépôts, et 100+ dépôts minimum
            if ($totalOperations >= 50 && $stats->total_depot >= 100) {
                $checkedCount++;
                $depotPercentage = ($stats->total_depot / $totalOperations) * 100;
                
                if ($depotPercentage >= 80) {
                    $matchedCount++;
                    $avgDepotAmount = $stats->avg_depot_amount ?? 0;
                    $severity = 'high';
                    
                    // Extra suspicious if average deposit amount is low (splits)
                    if ($avgDepotAmount > 0 && $avgDepotAmount < 5000) {
                        $severity = 'critical';
                    }

                    $alerts[] = [
                        'type' => 'split_deposit_fraud',
                        'pdv_id' => $pdv->id,
                        'pdv_name' => $pdv->nom_pdv,
                        'pdv_numero' => $pdv->numero_flooz,
                        'dealer_name' => $pdv->organization->name ?? 'Unknown',
                        'region' => $pdv->region,
                        'date' => $endDate,
                        'flagged_amount' => 0,
                        'description' => sprintf(
                            "Le PDV a effectué %d dépôts contre %d retraits sur la période du %s au %s (%.1f%% de dépôts). Montant moyen par dépôt: %s FCFA. Suspicion de split deposits pour multiplier les commissions.",
                            $stats->total_depot,
                            $stats->total_retrait,
                            Carbon::parse($startDate)->format('d/m/Y'),
                            Carbon::parse($endDate)->format('d/m/Y'),
                            $depotPercentage,
                            $avgDepotAmount > 0 ? number_format($avgDepotAmount, 0) : 'N/A'
                        ),
                        'severity_factors' => [
                            'depot_count' => $stats->total_depot,
                            'retrait_count' => $stats->total_retrait,
                            'depot_percentage' => round($depotPercentage, 1),
                            'total_operations' => $totalOperations,
                            'avg_depot_amount' => $avgDepotAmount,
                            'is_small_splits' => $avgDepotAmount > 0 && $avgDepotAmount < 5000,
                            'severity' => $severity,
                        ],
                    ];
                }
            }
        }

        Log::info("Split Deposit Detection Results", [
            'checked' => $checkedCount,
            'matched' => $matchedCount,
            'alerts' => count($alerts)
        ]);

        // Trier par pourcentage de dépôts décroissant (plus grand au plus petit)
        usort($alerts, function($a, $b) {
            return $b['severity_factors']['depot_percentage'] <=> $a['severity_factors']['depot_percentage'];
        });

        // Retourner toutes les alertes trouvées
        return $alerts;
    }

    /**
     * Detect off-hours large transactions
     */
    private function detectOffHoursLargeTransactions($scope, $entityId, $startDate, $endDate, $limit = 30, $offset = 0)
    {
        $alerts = [];
        $threshold = 500000; // 500k FCFA

        $pdvQuery = PointOfSale::where('status', 'validated');
        if ($scope === 'dealer' && $entityId) {
            $pdvQuery->where('organization_id', $entityId);
        } elseif ($scope === 'pdv' && $entityId) {
            $pdvQuery->where('id', $entityId);
        }
        $pdvNumeros = $pdvQuery->pluck('numero_flooz', 'id');

        // Since pdv_transactions is daily aggregated data, we can't detect exact hours
        // Instead, we'll look for unusual weekend/off-day high volume
        foreach ($pdvNumeros as $pdvId => $numeroFlooz) {
            $suspiciousPatterns = DB::table('pdv_transactions')
                ->where('pdv_numero', $numeroFlooz)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->whereRaw('DAYOFWEEK(transaction_date) IN (1, 7)') // Sunday=1, Saturday=7
                ->where(function ($q) use ($threshold) {
                    $q->where('sum_retrait', '>', $threshold)
                      ->orWhere('sum_depot', '>', $threshold);
                })
                ->get();

            foreach ($suspiciousPatterns as $pattern) {
                $pdv = PointOfSale::find($pdvId);
                $alerts[] = [
                    'type' => 'off_hours_large_transaction',
                    'pdv_id' => $pdvId,
                    'pdv_name' => $pdv->nom_pdv ?? 'Unknown',
                    'pdv_numero' => $numeroFlooz,
                    'dealer_name' => $pdv->organization->name ?? 'Unknown',
                    'region' => $pdv->region ?? 'Unknown',
                    'date' => $pattern->transaction_date,
                    'flagged_amount' => max($pattern->sum_retrait, $pattern->sum_depot),
                    'description' => "Transactions importantes le weekend: " . number_format(max($pattern->sum_retrait, $pattern->sum_depot)) . " FCFA",
                    'severity_factors' => [
                        'weekend_high_volume' => true,
                        'amount_exceeds_threshold' => true,
                    ],
                ];
            }
        }

        return $alerts;
    }

    /**
     * Detect sudden activity spikes
     */
    private function detectActivitySpikes($scope, $entityId, $startDate, $endDate, $limit = 30, $offset = 0)
    {
        $alerts = [];

        $pdvQuery = PointOfSale::where('status', 'validated');
        if ($scope === 'dealer' && $entityId) {
            $pdvQuery->where('organization_id', $entityId);
        } elseif ($scope === 'pdv' && $entityId) {
            $pdvQuery->where('id', $entityId);
        }
        // Scan all validated PDV for activity spikes
        $pdvs = $pdvQuery->with('organization')->get();

        foreach ($pdvs as $pdv) {
            // Get daily transaction volumes
            $dailyVolumes = DB::table('pdv_transactions')
                ->where('pdv_numero', $pdv->numero_flooz)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->selectRaw('
                    transaction_date,
                    (count_depot + count_retrait) as total_transactions,
                    (sum_depot + sum_retrait) as total_amount
                ')
                ->get();

            if ($dailyVolumes->count() > 7) {
                $avgVolume = $dailyVolumes->avg('total_transactions');
                $avgAmount = $dailyVolumes->avg('total_amount');

                foreach ($dailyVolumes as $day) {
                    if ($day->total_transactions > ($avgVolume * 3) && $avgVolume > 5) {
                        $alerts[] = [
                            'type' => 'activity_spike',
                            'pdv_id' => $pdv->id,
                            'pdv_name' => $pdv->nom_pdv,
                            'pdv_numero' => $pdv->numero_flooz,
                            'dealer_name' => $pdv->organization->name ?? 'Unknown',
                            'region' => $pdv->region,
                            'date' => $day->transaction_date,
                            'flagged_amount' => $day->total_amount,
                            // Note: moyenne calculée uniquement sur ce PDV pour la période analysée
                            'description' => "Pic d'activité anormal: " . $day->total_transactions . " transactions (moyenne de ce PDV: " . round($avgVolume) . ")",
                            'severity_factors' => [
                                'spike_multiplier' => round($day->total_transactions / max($avgVolume, 1), 2),
                                'amount' => $day->total_amount,
                            ],
                        ];
                    }
                }
            }
        }

        return $alerts;
    }

    /**
     * Detect PDV earning more in commissions than generating CA
     * This indicates excessive deposits (free for customer) vs retraits (generate CA)
     */
    private function detectCommissionOverCa($scope, $entityId, $startDate, $endDate, $limit = 30, $offset = 0)
    {
        $alerts = [];

        $pdvQuery = PointOfSale::where('status', 'validated');
        if ($scope === 'dealer' && $entityId) {
            $pdvQuery->where('organization_id', $entityId);
        } elseif ($scope === 'pdv' && $entityId) {
            $pdvQuery->where('id', $entityId);
        }
        // Scan all validated PDV for activity spikes
        $pdvs = $pdvQuery->with('organization')->get();

        foreach ($pdvs as $pdv) {
            $stats = DB::table('pdv_transactions')
                ->where('pdv_numero', $pdv->numero_flooz)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->selectRaw('
                    SUM(count_depot) as total_depot,
                    SUM(retrait_keycost) as total_ca,
                    SUM(sum_depot) as total_depot_amount
                ')
                ->first();

            if ($stats && $stats->total_ca > 0) {
                // Cast to numeric to avoid null/strings edge cases
                $totalDepot = (int) ($stats->total_depot ?? 0);
                $totalCa = (float) ($stats->total_ca ?? 0);

                // Ignore very low volumes (ex: 4 dépôts / 91 FCFA) qui ne sont pas suspects
                if ($totalDepot <= 25 || $totalCa < 1000) {
                    continue;
                }

                // Estimate commission: ~100 FCFA per depot transaction (adjust based on actual commission structure)
                $estimatedCommission = $totalDepot * 100;
                $commissionToCaRatio = $estimatedCommission / $totalCa;

                // Flag if estimated commission >= 100% of CA (ratio >= 2, meaning commission equals or exceeds 100% of CA)
                if ($commissionToCaRatio >= 2) {
                    $alerts[] = [
                        'type' => 'commission_over_ca',
                        'pdv_id' => $pdv->id,
                        'pdv_name' => $pdv->nom_pdv,
                        'pdv_numero' => $pdv->numero_flooz,
                        'dealer_name' => $pdv->organization->name ?? 'Unknown',
                        'region' => $pdv->region,
                        'date' => $endDate,
                        'flagged_amount' => $estimatedCommission - $stats->total_ca,
                        'description' => sprintf(
                            "Le PDV a effectué %d dépôts (commission estimée: %s FCFA) contre un CA de %s FCFA sur la période du %s au %s. Les commissions dépassent le CA de %.0f%%. Suspicion de fraude aux commissions.",
                            $totalDepot,
                            number_format($estimatedCommission, 0),
                            number_format($totalCa, 0),
                            Carbon::parse($startDate)->format('d/m/Y'),
                            Carbon::parse($endDate)->format('d/m/Y'),
                            ($commissionToCaRatio - 1) * 100
                        ),
                        'severity_factors' => [
                            'depot_count' => $totalDepot,
                            'estimated_commission' => $estimatedCommission,
                            'total_ca' => $totalCa,
                            'commission_to_ca_ratio' => round($commissionToCaRatio, 2),
                            'excess_percent' => round(($commissionToCaRatio - 1) * 100, 1),
                        ]
                    ];
                }
            }
        }

        return $alerts;
    }

    /**
     * Calculate risk score (0-100)
     */
    private function calculateRiskScore($alert)
    {
        $score = 0;

        switch ($alert['type']) {
            case 'split_deposit_fraud':
                // This is THE main fraud pattern
                $ratioMultiplier = $alert['severity_factors']['ratio_multiplier'] ?? 1;
                $isSmallSplits = $alert['severity_factors']['is_small_splits'] ?? false;
                
                $score = 70; // Base high score
                // Pondération objective sur le ratio dépôts/retraits
                // Seuil à 3x benchmark, puis progression linéaire jusqu'à +40
                $bonus = max(0, min(40, (($ratioMultiplier - 3) / 2) * 10));
                $score += $bonus;
                
                if ($isSmallSplits) {
                    $score += 10; // Extra suspicious if small amounts
                }
                break;
            case 'commission_over_ca':
                // Low priority: cas à surveiller sans alerter fort
                $score = 25; // Base faible
                if (isset($alert['severity_factors']['commission_to_ca_ratio'])) {
                    $ratio = $alert['severity_factors']['commission_to_ca_ratio'];
                    // Bonus modéré, plafonné pour rester en low/medium
                    $score += min(12, ($ratio - 1) * 6);
                }
                break;
            case 'off_hours_large_transaction':
                $score = 50; // Medium risk
                if ($alert['flagged_amount'] > 1000000) $score += 15;
                break;

            case 'activity_spike':
                $multiplier = $alert['severity_factors']['spike_multiplier'] ?? 1;
                $score = min(35 + ($multiplier * 8), 70);
                break;
        }

        return round(min(max($score, 0), 100));
    }

    /**
     * Get risk level based on score
     */
    private function getRiskLevel($score)
    {
        if ($score >= 70) return 'high';
        if ($score >= 40) return 'medium';
        return 'low';
    }

    /**
     * Export fraud detection alerts to Excel
     */
    public function exportExcel(Request $request)
    {
        $scope = $request->input('scope', 'global');
        $entityId = $request->input('entity_id');
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $cacheKey = "fraud_detection_export_{$scope}_{$entityId}_{$startDate}_{$endDate}";

        $alerts = \Cache::remember($cacheKey, 10800, function () use ($scope, $entityId, $startDate, $endDate) {
            $alerts = [];
            $alerts = array_merge($alerts, $this->detectSplitDepositFraud($scope, $entityId, $startDate, $endDate, 10000, 0));
            $alerts = array_merge($alerts, $this->detectOffHoursLargeTransactions($scope, $entityId, $startDate, $endDate, 10000, 0));
            $alerts = array_merge($alerts, $this->detectActivitySpikes($scope, $entityId, $startDate, $endDate, 10000, 0));
            $alerts = array_merge($alerts, $this->detectCommissionOverCa($scope, $entityId, $startDate, $endDate, 10000, 0));

            // Calculate risk scores
            foreach ($alerts as &$alert) {
                $alert['risk_score'] = $this->calculateRiskScore($alert);
                $alert['risk_level'] = $this->getRiskLevel($alert['risk_score']);
            }

            // Sort by risk score descending
            usort($alerts, function ($a, $b) {
                return $b['risk_score'] <=> $a['risk_score'];
            });

            return $alerts;
        });

        // Create Excel file
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Comportements Suspicieux');

        // Define headers
        $headers = [
            'A1' => 'Date',
            'B1' => 'PDV Numéro',
            'C1' => 'Nom PDV',
            'D1' => 'Dealer',
            'E1' => 'Région',
            'F1' => 'Type d\'Alerte',
            'G1' => 'Niveau de Risque',
            'H1' => 'Score de Risque',
            'I1' => 'Montant Suspect (FCFA)',
            'J1' => 'Description',
            'K1' => 'Détails Techniques'
        ];

        // Style headers
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F2937']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ];

        // Set headers
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);

        // Auto-size columns
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Set row height for header
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Fill data
        $row = 2;
        foreach ($alerts as $alert) {
            // Type labels mapping
            $typeLabels = [
                'split_deposit_fraud' => 'Fractionnement de Dépôts',
                'off_hours_transactions' => 'Transactions Hors Heures',
                'activity_spike' => 'Pic d\'Activité Suspect',
                'commission_over_ca' => 'Commission > CA'
            ];

            $riskLevelLabels = [
                'high' => 'ÉLEVÉ',
                'medium' => 'MOYEN',
                'low' => 'FAIBLE'
            ];

            // Build technical details
            $technicalDetails = [];
            if (isset($alert['severity_factors'])) {
                foreach ($alert['severity_factors'] as $key => $value) {
                    if (is_numeric($value)) {
                        $value = number_format($value, 2, ',', ' ');
                    }
                    $technicalDetails[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $value;
                }
            }

            $sheet->setCellValue('A' . $row, Carbon::parse($alert['date'])->format('d/m/Y'));
            $sheet->setCellValue('B' . $row, $alert['pdv_numero']);
            $sheet->setCellValue('C' . $row, $alert['pdv_name']);
            $sheet->setCellValue('D' . $row, $alert['dealer_name']);
            $sheet->setCellValue('E' . $row, $alert['region']);
            $sheet->setCellValue('F' . $row, $typeLabels[$alert['type']] ?? $alert['type']);
            $sheet->setCellValue('G' . $row, $riskLevelLabels[$alert['risk_level']] ?? $alert['risk_level']);
            $sheet->setCellValue('H' . $row, $alert['risk_score']);
            $sheet->setCellValue('I' . $row, number_format($alert['flagged_amount'], 0, ',', ' '));
            $sheet->setCellValue('J' . $row, $alert['description']);
            $sheet->setCellValue('K' . $row, implode(' | ', $technicalDetails));

            // Apply row styling based on risk level
            $rowStyle = [
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_TOP,
                    'wrapText' => true
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ]
            ];

            $sheet->getStyle('A' . $row . ':K' . $row)->applyFromArray($rowStyle);

            // Color code risk levels
            $riskColors = [
                'high' => 'FEE2E2',    // Red-100
                'medium' => 'FEF3C7',  // Yellow-100
                'low' => 'DBEAFE'      // Blue-100
            ];

            if (isset($riskColors[$alert['risk_level']])) {
                $sheet->getStyle('G' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $riskColors[$alert['risk_level']]]
                    ],
                    'font' => ['bold' => true]
                ]);
            }

            // Highlight risk score
            $sheet->getStyle('H' . $row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);

            // Format amount with alignment
            $sheet->getStyle('I' . $row)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
            ]);

            $row++;
        }

        // Set specific column widths for better readability
        $sheet->getColumnDimension('A')->setWidth(12);  // Date
        $sheet->getColumnDimension('B')->setWidth(15);  // PDV Numéro
        $sheet->getColumnDimension('C')->setWidth(25);  // Nom PDV
        $sheet->getColumnDimension('D')->setWidth(20);  // Dealer
        $sheet->getColumnDimension('E')->setWidth(15);  // Région
        $sheet->getColumnDimension('F')->setWidth(25);  // Type
        $sheet->getColumnDimension('G')->setWidth(15);  // Niveau
        $sheet->getColumnDimension('H')->setWidth(12);  // Score
        $sheet->getColumnDimension('I')->setWidth(18);  // Montant
        $sheet->getColumnDimension('J')->setWidth(50);  // Description
        $sheet->getColumnDimension('K')->setWidth(40);  // Détails

        // Add summary sheet
        $summarySheet = $spreadsheet->createSheet();
        $summarySheet->setTitle('Résumé');

        // Summary headers
        $summarySheet->setCellValue('A1', 'RÉSUMÉ DES COMPORTEMENTS SUSPICIEUX');
        $summarySheet->mergeCells('A1:B1');
        $summarySheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        $summarySheet->setCellValue('A3', 'Période analysée:');
        $summarySheet->setCellValue('B3', Carbon::parse($startDate)->format('d/m/Y') . ' - ' . Carbon::parse($endDate)->format('d/m/Y'));

        $summarySheet->setCellValue('A4', 'Nombre total d\'alertes:');
        $summarySheet->setCellValue('B4', count($alerts));

        $highRisk = count(array_filter($alerts, fn($a) => $a['risk_level'] === 'high'));
        $mediumRisk = count(array_filter($alerts, fn($a) => $a['risk_level'] === 'medium'));
        $lowRisk = count(array_filter($alerts, fn($a) => $a['risk_level'] === 'low'));

        $summarySheet->setCellValue('A6', 'Alertes Risque ÉLEVÉ:');
        $summarySheet->setCellValue('B6', $highRisk);
        $summarySheet->getStyle('B6')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'DC2626']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEE2E2']]
        ]);

        $summarySheet->setCellValue('A7', 'Alertes Risque MOYEN:');
        $summarySheet->setCellValue('B7', $mediumRisk);
        $summarySheet->getStyle('B7')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'D97706']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']]
        ]);

        $summarySheet->setCellValue('A8', 'Alertes Risque FAIBLE:');
        $summarySheet->setCellValue('B8', $lowRisk);
        $summarySheet->getStyle('B8')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '2563EB']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']]
        ]);

        $totalAmount = array_sum(array_column($alerts, 'flagged_amount'));
        $summarySheet->setCellValue('A10', 'Montant total suspect:');
        $summarySheet->setCellValue('B10', number_format($totalAmount, 0, ',', ' ') . ' FCFA');
        $summarySheet->getStyle('B10')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12]
        ]);

        // Type breakdown
        $summarySheet->setCellValue('A12', 'Répartition par type:');
        $summarySheet->getStyle('A12')->applyFromArray(['font' => ['bold' => true]]);

        $typeCount = [];
        foreach ($alerts as $alert) {
            $type = $alert['type'];
            $typeCount[$type] = ($typeCount[$type] ?? 0) + 1;
        }

        $summaryRow = 13;
        $typeLabels = [
            'split_deposit_fraud' => 'Fractionnement de Dépôts',
            'off_hours_transactions' => 'Transactions Hors Heures',
            'activity_spike' => 'Pic d\'Activité Suspect',
            'commission_over_ca' => 'Commission > CA'
        ];

        foreach ($typeCount as $type => $count) {
            $summarySheet->setCellValue('A' . $summaryRow, $typeLabels[$type] ?? $type);
            $summarySheet->setCellValue('B' . $summaryRow, $count);
            $summaryRow++;
        }

        $summarySheet->getColumnDimension('A')->setWidth(30);
        $summarySheet->getColumnDimension('B')->setWidth(25);

        // Set active sheet back to main data
        $spreadsheet->setActiveSheetIndex(0);

        // Generate filename
        $filename = 'comportements_suspicieux_' . Carbon::parse($startDate)->format('Ymd') . '_' . Carbon::parse($endDate)->format('Ymd') . '.xlsx';

        // Create writer and save to temporary file
        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'fraud_export_');
        $writer->save($tempFile);

        // Return as download response with proper CORS headers
        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ])->deleteFileAfterSend(true);
    }
}
