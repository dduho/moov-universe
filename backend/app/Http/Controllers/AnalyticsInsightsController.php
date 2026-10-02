<?php

namespace App\Http\Controllers;

use App\Models\PdvTransaction;
use App\Models\PointOfSale;
use App\Services\TransactionAggregates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AnalyticsInsightsController extends Controller
{
    /**
     * Formater un nombre avec séparateur de milliers (espace)
     */
    private function formatNumber($number)
    {
        return number_format($number, 0, ',', ' ');
    }
    
    /**
     * Formater une devise avec séparateur de milliers (espace)
     */
    private function formatCurrency($amount)
    {
        return number_format($amount, 2, ',', ' ') . ' FCFA';
    }
    
    /**
     * Générer des insights IA/ML sur les transactions
     */
    public function getInsights(Request $request)
    {
        $period = $request->input('period', 'month');
        $now = Carbon::now();
        
        // Cache de 30 minutes pour les insights
        $cacheKey = "ai_insights_{$period}_" . $now->format('Y-m-d');
        
        $insights = Cache::remember($cacheKey, 1800, function () use ($period, $now) {
            $insights = [];
            
            // 1. Détection des PDV inactifs inhabituellement
            $insights = array_merge($insights, $this->detectInactivePdv($now));
            
            // 2. Analyse des tendances de CA
            $insights = array_merge($insights, $this->analyzeCaTrends($now));
            
            // 3. Détection des anomalies de performance
            $insights = array_merge($insights, $this->detectPerformanceAnomalies($now));
            
            // 4. Recommandations pour booster le CA
            $insights = array_merge($insights, $this->generateRecommendations($now));
            
            // 5. Analyse des top performers
            $insights = array_merge($insights, $this->analyzeTopPerformers($now));
            
            return $insights;
        });
        
        return response()->json([
            'insights' => $insights,
            'generated_at' => now()->toIso8601String(),
        ]);
    }
    
    /**
     * Détecter les PDV inhabituellement inactifs
     */
    private function detectInactivePdv($now)
    {
        $insights = [];
        
        // Comparer les 7 derniers jours vs les 7 jours d'avant
        $lastWeekStart = $now->copy()->subDays(7);
        $lastWeekEnd = $now->copy();
        $previousWeekStart = $now->copy()->subDays(14);
        $previousWeekEnd = $now->copy()->subDays(7);
        
        // PDV actifs la semaine dernière
        $lastWeekActivePdv = DB::table('pdv_transactions')
            ->whereBetween('transaction_date', [$lastWeekStart, $lastWeekEnd])
            ->where(function($q) {
                $q->where('count_depot', '>', 0)
                  ->orWhere('count_retrait', '>', 0);
            })
            ->distinct()
            ->pluck('pdv_numero');
        
        // PDV actifs la semaine d'avant
        $previousWeekActivePdv = DB::table('pdv_transactions')
            ->whereBetween('transaction_date', [$previousWeekStart, $previousWeekEnd])
            ->where(function($q) {
                $q->where('count_depot', '>', 0)
                  ->orWhere('count_retrait', '>', 0);
            })
            ->distinct()
            ->pluck('pdv_numero');
        
        // PDV qui étaient actifs avant mais plus maintenant
        $nowInactive = $previousWeekActivePdv->diff($lastWeekActivePdv);
        
        if ($nowInactive->count() > 0) {
            $pdvDetails = PointOfSale::whereIn('numero_flooz', $nowInactive->take(5))
                ->select('numero_flooz', 'nom_point', 'region', 'organization_id')
                ->with(['organization:id,name'])
                ->get();
            
            $insights[] = [
                'type' => 'alert',
                'category' => 'inactivity',
                'severity' => 'high',
                'title' => '⚠️ PDV devenus inactifs',
                'message' => $this->formatNumber($nowInactive->count()) . " PDV actifs la semaine dernière sont maintenant inactifs.",
                'details' => $pdvDetails->map(fn($p) => [
                    'pdv' => $p->numero_flooz,
                    'nom' => $p->nom_point,
                    'dealer' => $p->organization->name ?? 'N/A',
                    'region' => $p->region,
                ])->toArray(),
                'recommendation' => 'Contactez ces PDV pour identifier les problèmes (manque de liquidité, problèmes techniques, fermeture).',
            ];
        }
        
        return $insights;
    }
    
    /**
     * Analyser les tendances de CA
     */
    private function analyzeCaTrends($now)
    {
        $insights = [];
        
        // CA des 7 derniers jours (synthèse quotidienne : mêmes totaux, bien moins de lignes)
        $lastWeek = DB::table('transaction_daily_summary')
            ->whereBetween('transaction_date', [$now->copy()->subDays(7), $now])
            ->sum('retrait_keycost');
        
        // CA des 7 jours d'avant
        $previousWeek = DB::table('transaction_daily_summary')
            ->whereBetween('transaction_date', [$now->copy()->subDays(14), $now->copy()->subDays(7)])
            ->sum('retrait_keycost');
        
        if ($previousWeek > 0) {
            $evolution = (($lastWeek - $previousWeek) / $previousWeek) * 100;
            
            if (abs($evolution) > 10) {
                $insights[] = [
                    'type' => $evolution > 0 ? 'success' : 'warning',
                    'category' => 'trend',
                    'severity' => abs($evolution) > 20 ? 'high' : 'medium',
                    'title' => $evolution > 0 ? '📈 Hausse significative du CA' : '📉 Baisse significative du CA',
                    'message' => sprintf(
                        'Le CA a %s de %.1f%% cette semaine (%s vs %s).',
                        $evolution > 0 ? 'augmenté' : 'baissé',
                        abs($evolution),
                        $this->formatCurrency($lastWeek),
                        $this->formatCurrency($previousWeek)
                    ),
                    'evolution' => round($evolution, 2),
                    'recommendation' => $evolution > 0 
                        ? 'Identifiez les facteurs de succès pour les reproduire (campagnes, nouveaux services, saisonnalité).'
                        : 'Analysez les causes de la baisse (problèmes réseau, manque de liquidité, concurrence, événements externes).',
                ];
            }
        }
        
        return $insights;
    }
    
    /**
     * Détecter les anomalies de performance
     */
    private function detectPerformanceAnomalies($now)
    {
        $insights = [];
        
        // Identifier les PDV avec chute brutale de CA
        $last7Days = $now->copy()->subDays(7);
        $previous7Days = $now->copy()->subDays(14);
        
        $pdvPerformance = DB::table('pdv_transactions as t1')
            ->select('t1.pdv_numero')
            ->selectRaw('
                SUM(CASE WHEN t1.transaction_date >= ? THEN t1.retrait_keycost ELSE 0 END) as ca_recent,
                SUM(CASE WHEN t1.transaction_date < ? AND t1.transaction_date >= ? THEN t1.retrait_keycost ELSE 0 END) as ca_previous
            ', [$last7Days, $last7Days, $previous7Days])
            ->where('transaction_date', '>=', $previous7Days)
            ->groupBy('t1.pdv_numero')
            ->havingRaw('ca_previous > 0 AND ca_recent > 0')
            ->get();
        
        $anomalies = [];
        foreach ($pdvPerformance as $perf) {
            $drop = (($perf->ca_recent - $perf->ca_previous) / $perf->ca_previous) * 100;
            if ($drop < -50) { // Chute de plus de 50%
                $anomalies[] = [
                    'pdv' => $perf->pdv_numero,
                    'drop' => round($drop, 2),
                    'ca_recent' => $perf->ca_recent,
                    'ca_previous' => $perf->ca_previous,
                ];
            }
        }
        
        if (count($anomalies) > 0) {
            usort($anomalies, fn($a, $b) => $a['drop'] <=> $b['drop']);
            $topAnomalies = array_slice($anomalies, 0, 5);
            
            $pdvDetails = PointOfSale::whereIn('numero_flooz', array_column($topAnomalies, 'pdv'))
                ->get()
                ->keyBy('numero_flooz');
            
            $insights[] = [
                'type' => 'alert',
                'category' => 'anomaly',
                'severity' => 'high',
                'title' => '🔍 Anomalies de performance détectées',
                'message' => $this->formatNumber(count($anomalies)) . ' PDV ont subi une chute de CA > 50% cette semaine.',
                'details' => array_map(function($a) use ($pdvDetails) {
                    $pdv = $pdvDetails[$a['pdv']] ?? null;
                    return [
                        'pdv' => $a['pdv'],
                        'nom' => $pdv ? $pdv->nom_point : 'Inconnu',
                        'dealer' => $pdv ? ($pdv->organization->name ?? null) : null,
                        'drop_percent' => $a['drop'],
                        'ca_recent' => $a['ca_recent'],
                        'ca_previous' => $a['ca_previous'],
                    ];
                }, $topAnomalies),
                'recommendation' => 'Enquêtez sur ces PDV : problèmes techniques, rupture de stock, ou événements locaux.',
            ];
        }
        
        return $insights;
    }
    
    /**
     * Générer des recommandations pour booster le CA
     */
    private function generateRecommendations($now)
    {
        $insights = [];
        
        // Analyser le ratio dépôts/retraits (synthèse quotidienne)
        $stats = DB::table('transaction_daily_summary')
            ->whereBetween('transaction_date', [$now->copy()->subDays(30), $now])
            ->selectRaw('
                SUM(count_depot) as total_depots,
                SUM(count_retrait) as total_retraits,
                SUM(sum_depot) as volume_depots,
                SUM(sum_retrait) as volume_retraits
            ')
            ->first();
        
        if ($stats->total_depots > 0 && $stats->total_retraits > 0) {
            $ratio = $stats->total_retraits / $stats->total_depots;
            
            if ($ratio > 2) {
                $insights[] = [
                    'type' => 'recommendation',
                    'category' => 'optimization',
                    'severity' => 'medium',
                    'title' => '💡 Opportunité : Équilibrer dépôts/retraits',
                    'message' => sprintf(
                        'Le ratio retraits/dépôts est de %.1f:1. Il y a %.0f%% plus de retraits que de dépôts.',
                        $ratio,
                        (($stats->total_retraits - $stats->total_depots) / $stats->total_depots) * 100
                    ),
                    'data' => [
                        'depots' => $stats->total_depots,
                        'retraits' => $stats->total_retraits,
                        'ratio' => round($ratio, 2),
                    ],
                    'recommendation' => 'Encouragez les dépôts via des campagnes promotionnelles pour améliorer la liquidité réseau et augmenter les commissions.',
                ];
            }
        }
        
        // Identifier les régions sous-performantes
        $regionStats = DB::table('pdv_transactions as t')
            ->join('point_of_sales as p', 't.pdv_numero', '=', 'p.numero_flooz')
            ->whereBetween('t.transaction_date', [$now->copy()->subDays(7), $now])
            ->whereNotNull('p.region')
            ->select('p.region')
            ->selectRaw('
                COUNT(DISTINCT CASE WHEN t.count_depot > 0 OR t.count_retrait > 0 THEN t.pdv_numero END) as pdv_actifs,
                COUNT(DISTINCT p.numero_flooz) as pdv_total,
                SUM(t.retrait_keycost) as ca_total
            ')
            ->groupBy('p.region')
            ->havingRaw('pdv_total > 0')
            ->get();
        
        foreach ($regionStats as $region) {
            $activationRate = ($region->pdv_actifs / $region->pdv_total) * 100;
            
            if ($activationRate < 30 && $region->pdv_total > 10) {
                $insights[] = [
                    'type' => 'recommendation',
                    'category' => 'regional',
                    'severity' => 'medium',
                    'title' => '🎯 Opportunité régionale : ' . $region->region,
                    'message' => sprintf(
                        'Seulement %.1f%% des PDV sont actifs dans %s (%s/%s PDV).',
                        $activationRate,
                        $region->region,
                        $this->formatNumber($region->pdv_actifs),
                        $this->formatNumber($region->pdv_total)
                    ),
                    'data' => [
                        'region' => $region->region,
                        'activation_rate' => round($activationRate, 1),
                        'pdv_actifs' => $region->pdv_actifs,
                        'pdv_total' => $region->pdv_total,
                    ],
                    'recommendation' => 'Déployez une campagne ciblée dans cette région : formations, support terrain, incentives pour réactiver les PDV dormants.',
                ];
            }
        }
        
        return $insights;
    }
    
    /**
     * Exporter les PDV liés à un insight (inactivity ou anomaly)
     * Retourne les données complètes des PDV au format identique à l'export classique
     */
    public function exportInsightPdv(Request $request)
    {
        $type = $request->input('type'); // 'inactivity' ou 'anomaly'
        $now = Carbon::now();

        if (!in_array($type, ['inactivity', 'anomaly'])) {
            return response()->json(['message' => 'Type invalide. Utilisez "inactivity" ou "anomaly".'], 422);
        }

        $pdvNumeros = collect();

        if ($type === 'inactivity') {
            // Reproduire la logique de detectInactivePdv pour récupérer TOUS les numéros
            $lastWeekStart = $now->copy()->subDays(7);
            $lastWeekEnd = $now->copy();
            $previousWeekStart = $now->copy()->subDays(14);
            $previousWeekEnd = $now->copy()->subDays(7);

            $lastWeekActivePdv = DB::table('pdv_transactions')
                ->whereBetween('transaction_date', [$lastWeekStart, $lastWeekEnd])
                ->where(function ($q) {
                    $q->where('count_depot', '>', 0)->orWhere('count_retrait', '>', 0);
                })
                ->distinct()->pluck('pdv_numero');

            $previousWeekActivePdv = DB::table('pdv_transactions')
                ->whereBetween('transaction_date', [$previousWeekStart, $previousWeekEnd])
                ->where(function ($q) {
                    $q->where('count_depot', '>', 0)->orWhere('count_retrait', '>', 0);
                })
                ->distinct()->pluck('pdv_numero');

            $pdvNumeros = $previousWeekActivePdv->diff($lastWeekActivePdv);

        } elseif ($type === 'anomaly') {
            // Reproduire la logique de detectPerformanceAnomalies pour récupérer TOUS les numéros
            $last7Days = $now->copy()->subDays(7);
            $previous7Days = $now->copy()->subDays(14);

            $pdvPerformance = DB::table('pdv_transactions as t1')
                ->select('t1.pdv_numero')
                ->selectRaw('
                    SUM(CASE WHEN t1.transaction_date >= ? THEN t1.retrait_keycost ELSE 0 END) as ca_recent,
                    SUM(CASE WHEN t1.transaction_date < ? AND t1.transaction_date >= ? THEN t1.retrait_keycost ELSE 0 END) as ca_previous
                ', [$last7Days, $last7Days, $previous7Days])
                ->where('transaction_date', '>=', $previous7Days)
                ->groupBy('t1.pdv_numero')
                ->havingRaw('ca_previous > 0 AND ca_recent > 0')
                ->get();

            $anomalyNumeros = [];
            foreach ($pdvPerformance as $perf) {
                $drop = (($perf->ca_recent - $perf->ca_previous) / $perf->ca_previous) * 100;
                if ($drop < -50) {
                    $anomalyNumeros[] = $perf->pdv_numero;
                }
            }
            $pdvNumeros = collect($anomalyNumeros);
        }

        if ($pdvNumeros->isEmpty()) {
            return response()->json(['data' => [], 'total' => 0]);
        }

        // Charger les PDV complets avec la relation organization (comme l'export classique)
        $pdvs = PointOfSale::whereIn('numero_flooz', $pdvNumeros)
            ->with(['organization:id,name'])
            ->get();

        return response()->json([
            'data' => $pdvs,
            'total' => $pdvs->count(),
        ]);
    }

    /**
     * Analyser les top performers pour identifier les best practices
     */
    private function analyzeTopPerformers($now)
    {
        $insights = [];
        
        // Trouver les top 10 PDV par CA
        // Agrégats par PDV ; bornes de l'ancienne requête (date > now-30j, jusqu'à aujourd'hui)
        $topPdv = TransactionAggregates::table(
                $now->copy()->subDays(30)->startOfDay()->addDay(),
                $now->copy()->startOfDay(),
                ['retrait_keycost', 'count_depot', 'count_retrait']
            )
            ->join('point_of_sales as p', 't.pdv_numero', '=', 'p.numero_flooz')
            ->join('organizations as o', 'p.organization_id', '=', 'o.id')
            ->select('t.pdv_numero', 'p.nom_point', 'p.region', 'o.name as dealer_name')
            ->selectRaw('
                t.retrait_keycost as ca_total,
                t.count_depot + t.count_retrait as total_transactions,
                t.retrait_keycost / t.days_count as ca_moyen_jour
            ')
            ->orderByDesc('ca_total')
            ->limit(10)
            ->get();
        
        if ($topPdv->isNotEmpty()) {
            $avgCa = $topPdv->avg('ca_moyen_jour');
            $topRegions = $topPdv->pluck('region')->unique();
            
            $insights[] = [
                'type' => 'success',
                'category' => 'best_practice',
                'severity' => 'low',
                'title' => '⭐ Insights des top performers',
                'message' => sprintf(
                    'Les 10 meilleurs PDV génèrent en moyenne %s/jour.',
                    $this->formatCurrency($avgCa)
                ),
                'data' => [
                    'top_regions' => $topRegions->values(),
                    'avg_daily_ca' => round($avgCa, 2),
                    'top_pdv_count' => $topPdv->count(),
                ],
                'recommendation' => 'Analysez les pratiques de ces PDV leaders et partagez les bonnes pratiques avec le réseau (horaires, services, communication client).',
            ];
        }
        
        return $insights;
    }
}
