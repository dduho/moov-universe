<?php

namespace App\Http\Controllers;

use App\Models\PdvTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Facades\Log;
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
                        'skipped' => $result['skipped'],
                        'date' => $result['date'],
                    ];
                    $results['total_imported'] += $result['imported'];
                    $results['total_skipped'] += $result['skipped'];
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
        // Augmenter temporairement la limite mémoire pour ce processus
        ini_set('memory_limit', '2G');
        
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

        // Vérifier que PDV_NUMERO existe
        if (!isset($columnIndexes['pdv_numero'])) {
            throw new \Exception("Colonne PDV_NUMERO introuvable dans le fichier");
        }

        // Obtenir la dernière ligne du fichier
        $highestRow = $worksheet->getHighestRow();
        
        // Récupérer tous les PDV existants pour cette date en une seule requête
        $existingPdvs = PdvTransaction::where('transaction_date', $transactionDate->format('Y-m-d'))
            ->pluck('pdv_numero')
            ->flip()
            ->toArray();
        
        // Lire les données à partir de la ligne 12 jusqu'à la dernière ligne
        $imported = 0;
        $skipped = 0;
        $batchData = [];
        $batchSize = 500; // Insérer par lots de 500

        for ($row = 12; $row <= $highestRow; $row++) {
            $pdvNumeroRef = Coordinate::stringFromColumnIndex($columnIndexes['pdv_numero']) . $row;
            $pdvNumero = $worksheet->getCell($pdvNumeroRef)->getValue();
            
            // Ignorer seulement les vraies lignes vides (pas les PDV avec valeur 0)
            if ($pdvNumero === null || trim($pdvNumero) === '') {
                $skipped++;
                continue;
            }

            $pdvNumeroTrimmed = trim($pdvNumero);
            
            // Vérifier si l'entrée existe déjà dans la base ou dans le batch en cours
            if (isset($existingPdvs[$pdvNumeroTrimmed])) {
                $skipped++;
                continue;
            }

            // Préparer les données
            $data = [
                'pdv_numero' => $pdvNumeroTrimmed,
                'transaction_date' => $transactionDate->format('Y-m-d'),
            ];

            // Extraire toutes les valeurs des colonnes
            foreach ($columnIndexes as $dbColumn => $excelIndex) {
                if ($dbColumn !== 'pdv_numero') {
                    $cellRef = Coordinate::stringFromColumnIndex($excelIndex) . $row;
                    $value = $worksheet->getCell($cellRef)->getValue();
                    $data[$dbColumn] = $this->normalizeValue($value);
                }
            }

            $data['created_at'] = now();
            $data['updated_at'] = now();
            
            $batchData[] = $data;
            $existingPdvs[$pdvNumeroTrimmed] = true; // Marquer comme existant pour éviter les doublons dans le fichier

            // Insérer par lot quand on atteint la taille du batch
            if (count($batchData) >= $batchSize) {
                PdvTransaction::insert($batchData);
                $imported += count($batchData);
                $batchData = [];
            }
        }
        
        // Insérer les données restantes
        if (!empty($batchData)) {
            PdvTransaction::insert($batchData);
            $imported += count($batchData);
        }
        
        Log::info("Import terminé: {$imported} importés, {$skipped} ignorés (lignes vides ou doublons)");

        return [
            'imported' => $imported,
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
     * Normaliser les valeurs (gérer les virgules, espaces, etc.)
     */
    private function normalizeValue($value)
    {
        if (is_null($value) || $value === '') {
            return 0;
        }

        // Supprimer les espaces
        $value = trim($value);

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
