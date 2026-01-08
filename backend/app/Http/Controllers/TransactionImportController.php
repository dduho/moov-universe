<?php

namespace App\Http\Controllers;

use App\Models\PdvTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class TransactionImportController extends Controller
{
    /**
     * Import des fichiers de transactions Excel
     */
    public function import(Request $request)
    {
        try {
            $request->validate([
                'files.*' => 'required|file|mimes:xls,xlsx|max:512000', // Max 500MB par fichier
            ]);

            $results = [
                'success' => [],
                'errors' => [],
                'total_imported' => 0,
                'total_updated' => 0,
                'total_skipped' => 0,
            ];

            if (!$request->hasFile('files')) {
                return response()->json(['error' => 'Aucun fichier fourni'], 400);
            }

            foreach ($request->file('files') as $file) {
                try {
                    $result = $this->processFile($file);
                    $results['success'][] = [
                        'filename' => $file->getClientOriginalName(),
                        'imported' => $result['imported'],
                        'updated' => $result['updated'],
                        'skipped' => $result['skipped'],
                        'date' => $result['date'],
                    ];
                    $results['total_imported'] += $result['imported'];
                    $results['total_updated'] += $result['updated'];
                    $results['total_skipped'] += $result['skipped'];
                    
                    // Invalider le cache analytics pour cette date
                    $this->invalidateAnalyticsCache($result['date']);
                } catch (\Exception $e) {
                    Log::error('Erreur import transaction: ' . $e->getMessage(), [
                        'file' => $file->getClientOriginalName(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    $results['errors'][] = [
                        'filename' => $file->getClientOriginalName(),
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return response()->json($results);
        } catch (\Exception $e) {
            Log::error('Erreur générale import transactions: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Erreur lors de l\'import: ' . $e->getMessage(),
                'success' => [],
                'errors' => [],
                'total_imported' => 0,
                'total_updated' => 0,
                'total_skipped' => 0,
            ], 500);
        }
    }

    /**
     * Exposé pour usage programmatique (cron, jobs...)
     */
    public function importUploadedFile(UploadedFile $file): array
    {
        return $this->processFile($file);
    }

    /**
     * Traiter un fichier Excel
     */
    private function processFile(UploadedFile $file)
    {
        // Augmenter temporairement la limite mémoire et temps d'exécution pour ce processus
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', '600'); // 10 minutes
        set_time_limit(600); // 10 minutes
        
        // Configuration de PhpSpreadsheet pour optimiser la mémoire
        \PhpOffice\PhpSpreadsheet\Settings::setCache(
            new \PhpOffice\PhpSpreadsheet\Collection\Memory\SimpleCache3()
        );
        
        $spreadsheet = IOFactory::load($file->getRealPath());
        $worksheet = $spreadsheet->getActiveSheet();

        // Extraire la date depuis A6 (format: "Start Date: 15/12/2025")
        $dateCell = $worksheet->getCell('A6')->getValue();
        $transactionDate = $this->extractDate($dateCell);

        if (!$transactionDate) {
            throw new \Exception("Impossible d'extraire la date du fichier (cellule A6)");
        }

        // Lire les en-têtes de colonnes à la ligne 11
        $headers = [];
        $columnIndex = 1; // Commence à la colonne A
        while (true) {
            $cellRef = Coordinate::stringFromColumnIndex($columnIndex) . '11';
            $cellValue = $worksheet->getCell($cellRef)->getValue();
            if (empty($cellValue)) {
                break; // Arrêter si on atteint une cellule vide
            }
            $headers[$columnIndex] = trim($cellValue);
            $columnIndex++;
        }

        // Mapper les noms de colonnes Excel vers les noms de base de données
        $columnMapping = $this->getColumnMapping();

        // Trouver les index des colonnes qui nous intéressent
        $columnIndexes = [];
        foreach ($headers as $index => $header) {
            if (isset($columnMapping[$header])) {
                $columnIndexes[$columnMapping[$header]] = $index;
            }
        }
        
        // Log des en-têtes pour debug
        Log::info("Import Excel - En-têtes détectés", [
            'headers' => $headers,
            'mapped_columns' => array_keys($columnIndexes),
            'missing_columns' => array_diff(array_keys($columnMapping), $headers)
        ]);

        // Vérifier que PDV_NUMERO existe
        if (!isset($columnIndexes['pdv_numero'])) {
            throw new \Exception("Colonne PDV_NUMERO introuvable dans le fichier");
        }

        // Obtenir la dernière ligne du fichier
        $highestRow = $worksheet->getHighestRow();
        
        // Lire les données à partir de la ligne 12 jusqu'à la dernière ligne
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $allData = [];
        $batchSize = 1000; // Traiter par lots de 1000
        $debugFirstRow = true; // Debug mode pour la première ligne

        for ($row = 12; $row <= $highestRow; $row++) {
            $pdvNumeroRef = Coordinate::stringFromColumnIndex($columnIndexes['pdv_numero']) . $row;
            $pdvNumero = $worksheet->getCell($pdvNumeroRef)->getValue();
            
            // Ignorer seulement les vraies lignes vides (pas les PDV avec valeur 0)
            if ($pdvNumero === null || trim($pdvNumero) === '') {
                $skipped++;
                continue;
            }

            $pdvNumeroTrimmed = trim($pdvNumero);
            
            // Préparer les données
            $data = [
                'pdv_numero' => $pdvNumeroTrimmed,
                'transaction_date' => $transactionDate->format('Y-m-d'),
            ];

            // Extraire toutes les valeurs des colonnes
            foreach ($columnIndexes as $dbColumn => $excelIndex) {
                if ($dbColumn !== 'pdv_numero') {
                    $cellRef = Coordinate::stringFromColumnIndex($excelIndex) . $row;
                    $cell = $worksheet->getCell($cellRef);
                    
                    // Essayer d'obtenir la valeur calculée si c'est une formule
                    try {
                        $value = $cell->getCalculatedValue();
                    } catch (\Exception $e) {
                        $value = $cell->getValue();
                    }
                    
                    $normalizedValue = $this->normalizeValue($value);
                    $data[$dbColumn] = $normalizedValue;
                    
                    // Debug pour la première ligne
                    if ($debugFirstRow && $row == 12 && in_array($dbColumn, ['count_depot', 'sum_depot', 'count_retrait', 'retrait_keycost'])) {
                        Log::info("DEBUG Excel extraction ligne 12", [
                            'column' => $dbColumn,
                            'cell_ref' => $cellRef,
                            'raw_value' => $value,
                            'raw_type' => gettype($value),
                            'normalized' => $normalizedValue
                        ]);
                    }
                }
            }
            
            if ($debugFirstRow && $row == 12) {
                $debugFirstRow = false; // Ne logger que la première ligne
            }

            $data['created_at'] = now();
            $data['updated_at'] = now();
            
            $allData[] = $data;

            // Traiter par lot quand on atteint la taille du batch
            if (count($allData) >= $batchSize) {
                $result = $this->upsertBatch($allData, $transactionDate->format('Y-m-d'));
                $imported += $result['inserted'];
                $updated += $result['updated'];
                $allData = [];
            }
        }
        
        // Traiter les données restantes
        if (!empty($allData)) {
            $result = $this->upsertBatch($allData, $transactionDate->format('Y-m-d'));
            $imported += $result['inserted'];
            $updated += $result['updated'];
        }
        
        Log::info("Import terminé: {$imported} créés, {$updated} mis à jour, {$skipped} ignorés (lignes vides)");

        return [
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'date' => $transactionDate->format('Y-m-d'),
        ];
    }

    /**
     * Extraire la date depuis la cellule A6
     */
    private function extractDate($cellValue)
    {
        if (empty($cellValue)) {
            return null;
        }

        // Rechercher un pattern de date (15/12/2025, 15-12-2025, etc.)
        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $cellValue, $matches)) {
            try {
                return Carbon::createFromFormat('d/m/Y', $matches[1] . '/' . $matches[2] . '/' . $matches[3]);
            } catch (\Exception $e) {
                Log::warning("Format de date non reconnu: $cellValue");
                return null;
            }
        }

        return null;
    }

    /**
     * Upsert batch optimisé avec Laravel upsert()
     */
    private function upsertBatch(array $data, string $date): array
    {
        // Compter les existants avant
        $existingPdvs = PdvTransaction::where('transaction_date', $date)
            ->pluck('pdv_numero')
            ->flip()
            ->toArray();
        
        $beforeCount = count($existingPdvs);
        
        // Colonnes qui définissent l'unicité
        $uniqueBy = ['pdv_numero', 'transaction_date'];
        
        // Colonnes à mettre à jour en cas de conflit (basé sur la structure réelle de la table)
        $update = [
            'count_depot',
            'sum_depot',
            'pdv_depot_commission',
            'dealer_depot_commission',
            'pdv_depot_retenue',
            'dealer_depot_retenue',
            'depot_keycost',
            'depot_customer_tva',
            'count_retrait',
            'sum_retrait',
            'pdv_retrait_commission',
            'dealer_retrait_commission',
            'pdv_retrait_retenue',
            'dealer_retrait_retenue',
            'retrait_keycost',
            'retrait_customer_tva',
            'count_give_send',
            'sum_give_send',
            'count_give_send_in_network',
            'sum_give_send_in_network',
            'count_give_send_out_network',
            'sum_give_send_out_network',
            'count_give_receive',
            'sum_give_receive',
            'count_give_receive_in_network',
            'sum_give_receive_in_network',
            'count_give_receive_out_network',
            'sum_give_receive_out_network',
            'updated_at'
        ];
        
        // Effectuer l'upsert en une seule requête batch
        PdvTransaction::upsert($data, $uniqueBy, $update);
        
        // Compter les nouveaux après
        $afterPdvs = PdvTransaction::where('transaction_date', $date)
            ->pluck('pdv_numero')
            ->flip()
            ->toArray();
        
        $afterCount = count($afterPdvs);
        
        // Calculer inserted vs updated
        $inserted = max(0, $afterCount - $beforeCount);
        $updated = count($data) - $inserted;
        
        return [
            'inserted' => $inserted,
            'updated' => $updated
        ];
    }

    /**
     * Normaliser les valeurs (gérer les virgules, espaces, etc.)
     */
    private function normalizeValue($value)
    {
        if (is_null($value) || $value === '') {
            return 0;
        }

        // Convertir en string si c'est un objet (PhpSpreadsheet peut retourner des objets RichText)
        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                $value = (string) $value;
            } else if (method_exists($value, 'getPlainText')) {
                $value = $value->getPlainText();
            } else {
                return 0;
            }
        }

        // Supprimer les espaces
        $value = trim($value);

        // Si c'est vide après trim
        if ($value === '') {
            return 0;
        }

        // Si c'est déjà un nombre, le retourner
        if (is_numeric($value)) {
            return $value;
        }

        // Gérer les formats avec espaces (1 000 000) et virgules comme séparateurs de milliers
        // Les points sont des séparateurs décimaux, donc on les garde
        $value = str_replace(' ', '', $value);
        // Supprimer uniquement les virgules utilisées comme séparateurs de milliers (ex: 1,000.50)
        $value = str_replace(',', '', $value);

        return is_numeric($value) ? $value : 0;
    }

    /**
     * Invalider le cache analytics après import et recalculer immédiatement
     */
    private function invalidateAnalyticsCache($date)
    {
        try {
            $carbonDate = Carbon::parse($date);
            
            // Invalider les clés de cache qui pourraient contenir cette date
            // Format: analytics_{period}_{start}_{end}
            $periods = ['day', 'week', 'month', 'quarter'];
            $dateStr = $carbonDate->format('Y-m-d');
            
            // Invalider les clés de cache analytics avec tags
            try {
                Cache::tags(['analytics', 'transactions'])->flush();
            } catch (\Exception $e) {
                // Si les tags ne sont pas supportés ou FLUSHDB désactivé, on ignore
                Log::warning('Cache flush failed (Redis command may be disabled): ' . $e->getMessage());
            }
            
            // Recalculer IMMÉDIATEMENT les analytics pour cette date (synchrone)
            // Ceci garantit que les données sont disponibles avant que la réponse ne soit renvoyée
            Artisan::call('analytics:cache-daily', [
                'date' => $date
            ]);
            
            Log::info("Cache analytics invalidé et recalculé pour la date: {$date}");
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'invalidation du cache analytics: " . $e->getMessage());
        }
    }

    /**
     * Mapping entre les noms de colonnes Excel et les noms de base de données
     */
    private function getColumnMapping()
    {
        return [
            'PDV_NUMERO' => 'pdv_numero',
            'COUNT_DEPOT' => 'count_depot',
            'SUM_DEPOT' => 'sum_depot',
            'PDV_DEPOT_COMMISSION' => 'pdv_depot_commission',
            'DEALER_DEPOT_COMMISSION' => 'dealer_depot_commission',
            'PDV_DEPOT_RETENUE' => 'pdv_depot_retenue',
            'DEALER_DEPOT_RETENUE' => 'dealer_depot_retenue',
            'DEPOT_KEYCOST' => 'depot_keycost',
            'DEPOT_CUSTOMER_TVA' => 'depot_customer_tva',
            'COUNT_RETRAIT' => 'count_retrait',
            'SUM_RETRAIT' => 'sum_retrait',
            'PDV_RETRAIT_COMMISSION' => 'pdv_retrait_commission',
            'DEALER_RETRAIT_COMMISSION' => 'dealer_retrait_commission',
            'PDV_RETRAIT_RETENUE' => 'pdv_retrait_retenue',
            'DEALER_RETRAIT_RETENUE' => 'dealer_retrait_retenue',
            'RETRAIT_KEYCOST' => 'retrait_keycost',
            'RETRAIT_CUSTOMER_TVA' => 'retrait_customer_tva',
            'COUNT_GIVE_SEND' => 'count_give_send',
            'SUM_GIVE_SEND' => 'sum_give_send',
            'COUNT_GIVE_SEND_IN_NETWORK' => 'count_give_send_in_network',
            'SUM_GIVE_SEND_IN_NETWORK' => 'sum_give_send_in_network',
            'COUNT_GIVE_SEND_OUT_NETWORK' => 'count_give_send_out_network',
            'SUM_GIVE_SEND_OUT_NETWORK' => 'sum_give_send_out_network',
            'COUNT_GIVE_RECEIVE' => 'count_give_receive',
            'SUM_GIVE_RECEIVE' => 'sum_give_receive',
            'COUNT_GIVE_RECEIVE_IN_NETWORK' => 'count_give_receive_in_network',
            'SUM_GIVE_RECEIVE_IN_NETWORK' => 'sum_give_receive_in_network',
            'COUNT_GIVE_RECEIVE_OUT_NETWORK' => 'count_give_receive_out_network',
            'SUM_GIVE_RECEIVE_OUT_NETWORK' => 'sum_give_receive_out_network',
        ];
    }
}
