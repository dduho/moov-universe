<?php

namespace App\Http\Controllers;

use App\Models\PointOfSale;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PointOfSaleImportController extends Controller
{
    /**
     * Valide et prévisualise les données avant import (dry run)
     */
    public function preview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
            'organization_id' => 'required|exists:organizations,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Données invalides',
                'errors' => $validator->errors()
            ], 400);
        }

        $file = $request->file('file');
        $organizationId = $request->organization_id;

        try {
            // Charger le fichier Excel/CSV
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (empty($rows)) {
                return response()->json([
                    'error' => 'Le fichier est vide'
                ], 400);
            }

            // Extraire les en-têtes (première ligne)
            $headers = $rows[0];
            
            // Mapper les en-têtes aux colonnes
            $headerMap = $this->mapHeaders($headers);

            // Vérifier UNIQUEMENT les en-têtes essentiels
            $requiredHeaders = [
                'nom_point', 'numero_flooz', 'region'
            ];

            $missingHeaders = [];
            foreach ($requiredHeaders as $required) {
                if (!isset($headerMap[$required])) {
                    $missingHeaders[] = $required;
                }
            }

            if (!empty($missingHeaders)) {
                return response()->json([
                    'error' => 'En-têtes essentiels manquants: ' . implode(', ', $missingHeaders),
                    'headers_found' => array_map('strtolower', array_map('trim', $headers)),
                    'headers_required' => $requiredHeaders,
                    'headers_mapped' => array_keys($headerMap),
                    'note' => 'Les colonnes optionnelles manquantes seront ignorées'
                ], 400);
            }

            // Traiter les données
            $validRows = [];
            $invalidRows = [];
            $duplicates = [];
            $toUpdate = [];
            
            // Suivre les shortcodes et numéros Flooz déjà vus dans le fichier
            $seenShortcodes = [];
            $seenNumeros = [];

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                
                // Sauter les lignes vides
                if (empty(array_filter($row))) {
                    continue;
                }

                $data = $this->mapRowData($row, $headerMap, $organizationId);
                
                // Valider les données
                $validator = $this->validateRow($data, $i + 1);
                
                if ($validator->fails()) {
                    $invalidRows[] = [
                        'line' => $i + 1,
                        'data' => $data,
                        'errors' => $validator->errors()->all()
                    ];
                } else {
                    // Vérifier si c'est un doublon DANS LE FICHIER
                    $isDuplicateInFile = false;
                    
                    if (!empty($data['shortcode'])) {
                        if (isset($seenShortcodes[$data['shortcode']])) {
                            $isDuplicateInFile = true;
                            $duplicates[] = [
                                'line' => $i + 1,
                                'data' => $data,
                                'duplicate_of_line' => $seenShortcodes[$data['shortcode']],
                                'message' => "Doublon dans le fichier (même shortcode à la ligne {$seenShortcodes[$data['shortcode']]})"
                            ];
                        } else {
                            $seenShortcodes[$data['shortcode']] = $i + 1;
                        }
                    }
                    
                    if (!$isDuplicateInFile && isset($seenNumeros[$data['numero_flooz']])) {
                        $isDuplicateInFile = true;
                        $duplicates[] = [
                            'line' => $i + 1,
                            'data' => $data,
                            'duplicate_of_line' => $seenNumeros[$data['numero_flooz']],
                            'message' => "Doublon dans le fichier (même numéro Flooz à la ligne {$seenNumeros[$data['numero_flooz']]})"
                        ];
                    } else {
                        $seenNumeros[$data['numero_flooz']] = $i + 1;
                    }
                    
                    // Si pas un doublon dans le fichier, vérifier si existe en base
                    if (!$isDuplicateInFile) {
                        $existing = null;
                        if (!empty($data['shortcode'])) {
                            $existing = PointOfSale::where('shortcode', $data['shortcode'])->first();
                        }
                        if (!$existing) {
                            $existing = PointOfSale::where('numero_flooz', $data['numero_flooz'])->first();
                        }
                        
                        if ($existing) {
                            $toUpdate[] = [
                                'line' => $i + 1,
                                'data' => $data,
                                'existing_id' => $existing->id,
                                'existing_shortcode' => $existing->shortcode,
                                'message' => "PDV existant (ID: {$existing->id}" . ($existing->shortcode ? ", Shortcode: {$existing->shortcode}" : ", Numéro: {$existing->numero_flooz}") . ") - Sera mis à jour"
                            ];
                        } else {
                            $validRows[] = [
                                'line' => $i + 1,
                                'data' => $data
                            ];
                        }
                    }
                }
            }

            return response()->json([
                'summary' => [
                    'total_lines' => count($rows) - 1,
                    'valid' => count($validRows),
                    'to_update' => count($toUpdate),
                    'invalid' => count($invalidRows),
                    'duplicates' => count($duplicates)
                ],
                'valid_rows' => $validRows,
                'to_update' => $toUpdate,
                'invalid_rows' => $invalidRows,
                'duplicates' => $duplicates,
                'headers' => $headers
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la lecture du fichier: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import définitif des données
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'organization_id' => 'required|exists:organizations,id',
            'skip_duplicates' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Données invalides',
                'errors' => $validator->errors()
            ], 400);
        }

        $file = $request->file('file');
        $organizationId = $request->organization_id;
        $skipDuplicates = $request->boolean('skip_duplicates', true);

        DB::beginTransaction();

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $headers = array_map('trim', array_map('strtolower', $rows[0]));
            $headerMap = $this->mapHeaders($headers);

            $imported = [];
            $updated = [];
            $skipped = [];
            $errors = [];

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                
                if (empty(array_filter($row))) {
                    continue;
                }

                $data = $this->mapRowData($row, $headerMap, $organizationId);
                
                // Valider
                $validator = $this->validateRow($data, $i + 1);
                
                if ($validator->fails()) {
                    $errors[] = [
                        'line' => $i + 1,
                        'errors' => $validator->errors()->all()
                    ];
                    continue;
                }

                // Vérifier si le PDV existe (par shortcode d'abord, puis par numero_flooz)
                $existing = null;
                if (!empty($data['shortcode'])) {
                    $existing = PointOfSale::where('shortcode', $data['shortcode'])->first();
                }
                if (!$existing) {
                    $existing = PointOfSale::where('numero_flooz', $data['numero_flooz'])->first();
                }
                
                if ($existing) {
                    // Mettre à jour le PDV existant
                    $existing->update($data);
                    $updated[] = [
                        'line' => $i + 1,
                        'id' => $existing->id,
                        'nom_point' => $existing->nom_point,
                        'shortcode' => $existing->shortcode
                    ];
                } else {
                    // Créer un nouveau PDV
                    $pdv = PointOfSale::create($data);
                    $imported[] = [
                        'line' => $i + 1,
                        'id' => $pdv->id,
                        'nom_point' => $pdv->nom_point,
                        'shortcode' => $pdv->shortcode
                    ];
                }
            }

            if (!empty($errors)) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Des erreurs ont été détectées',
                    'errors' => $errors
                ], 422);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'summary' => [
                    'imported' => count($imported),
                    'updated' => count($updated),
                    'skipped' => count($skipped),
                    'errors' => count($errors)
                ],
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Import error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Erreur lors de l\'import: ' . $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Mapper les en-têtes aux noms de colonnes
     */
    private function mapHeaders($headers)
    {
        $map = [];
        
        // Mapping des variantes de noms de colonnes
        $aliases = [
            'nom_point' => ['nom_point', 'nom_du_point', 'nom du point', 'point', 'nom'],
            'numero_flooz' => ['numero_flooz', 'numero flooz', 'numéro_flooz', 'numéro flooz', 'flooz', 'numero'],
            'shortcode' => ['shortcode', 'short_code', 'short code', 'code'],
            'profil' => ['profil', 'profile', 'type'],
            'region' => ['region', 'région'],
            'prefecture' => ['prefecture', 'préfecture'],
            'commune' => ['commune'],
            'ville' => ['ville', 'city'],
            'quartier' => ['quartier', 'quarter'],
            'canton' => ['canton'],
            'dealer_name' => ['dealer_name', 'dealer name', 'dealer', 'nom_dealer'],
            'latitude' => ['latitude', 'lat'],
            'longitude' => ['longitude', 'long', 'lng'],
            // Informations gérant
            'firstname' => ['firstname', 'firstname prenom', 'prenom', 'prénom'],
            'lastname' => ['lastname', 'lastname nom', 'nom', 'nom de famille'],
            'gender' => ['gender', 'gender sexe', 'sexe', 'sexe du gerant', 'sexe_gerant', 'sexe gerant'],
            'date_of_birth' => ['date_of_birth', 'date of birth', 'date de naissance', 'datenaissance'],
            // Documents
            'id_type' => ['id_type', 'iddescription', 'iddescription type de piece', 'type de piece', 'type_piece'],
            'id_number' => ['id_number', 'idnumber', 'idnumber numero de piece', 'numero de piece', 'numero_piece'],
            'id_expiry_date' => ['id_expiry', 'id_expiry_date', 'idexpirydate', 'idexpirydate date d expiration', 'date d expiration', 'date_expiration'],
            'nationality' => ['nationality', 'nationality nationalite', 'nationalite', 'nationalité'],
            'profession' => ['profession', 'profession profession'],
            'type_activite' => ['type_activite', 'type d activite', "type d'activite", 'type activite', 'activite'],
            // Fiscalité
            'nif' => ['nif'],
            'regime_fiscal' => ['regime_fiscal', 'regime fiscal'],
            // Contacts
            'phone' => ['phone', 'telephone', 'téléphone', 'numero proprietaire du pdv', 'contact'],
            'autre_contact' => ['autre_contact', 'autre contact', 'autre contact du pdv'],
            // Visibilité
            'support_visibilite' => ['support_visibilite', 'support de visibilite', 'support visibilite', 'support'],
            'etat_support' => ['etat_support', 'etat du support', 'etat du support de visibilite', 'etat support', 'etat'],
            'numero_cagnt' => ['numero_cagnt', 'numero cagnt', 'cagnt'],
        ];
        
        foreach ($headers as $index => $header) {
            $normalized = strtolower(trim($header));
            // Nettoyer les slashes, retours à la ligne, parenthèses et leur contenu
            $normalized = str_replace(['/', '\\n', '\\r', "\n", "\r"], ' ', $normalized);
            // Retirer tout ce qui est entre parenthèses (descriptions supplémentaires)
            $normalized = preg_replace('/\([^)]*\)/', '', $normalized);
            // Multiples espaces -> un seul
            $normalized = preg_replace('/\s+/', ' ', $normalized);
            $normalized = trim($normalized);
            
            // Chercher la correspondance dans les alias
            foreach ($aliases as $canonical => $variants) {
                foreach ($variants as $variant) {
                    if ($normalized === $variant || str_replace('_', ' ', $normalized) === $variant) {
                        $map[$canonical] = $index;
                        break 2;
                    }
                }
            }
        }
        
        return $map;
    }

    /**
     * Mapper une ligne de données
     */
    private function mapRowData($row, $headerMap, $organizationId)
    {
        $user = auth()->user();
        
        // Récupérer le profil directement sans transformation
        $profil = $this->getColumnValue($row, $headerMap, 'profil');
        $profil = $profil ? strtoupper(trim($profil)) : null;
        
        // Récupérer et normaliser les champs géographiques (espaces -> underscores)
        $prefecture = $this->getColumnValue($row, $headerMap, 'prefecture');
        $prefecture = $this->normalizeCommune($prefecture) ?: 'Non spécifié';
        
        $commune = $this->getColumnValue($row, $headerMap, 'commune');
        $commune = $this->normalizeCommune($commune) ?: 'Non spécifié';
        
        $canton = $this->getColumnValue($row, $headerMap, 'canton');
        $canton = $this->normalizeCommune($canton);
        
        $ville = $this->getColumnValue($row, $headerMap, 'ville');
        $ville = $this->normalizeCommune($ville) ?: 'Non spécifié';
        
        $quartier = $this->getColumnValue($row, $headerMap, 'quartier');
        $quartier = $this->normalizeCommune($quartier) ?: 'Non spécifié';
        
        // Récupérer et normaliser le sexe
        $gender = $this->getColumnValue($row, $headerMap, 'gender');
        $gender = $this->normalizeGender($gender);
        
        $sexe_gerant = $this->getColumnValue($row, $headerMap, 'sexe_gerant');
        $sexe_gerant = $this->normalizeGender($sexe_gerant);
        
        // Récupérer et normaliser NIF et régime fiscal
        $nif = $this->getColumnValue($row, $headerMap, 'nif');
        $nif = $this->normalizeNIF($nif);
        
        $regime_fiscal = $this->getColumnValue($row, $headerMap, 'regime_fiscal');
        $regime_fiscal = $this->normalizeRegimeFiscal($regime_fiscal);
        
        // Récupérer et normaliser le type de pièce
        $id_type = $this->getColumnValue($row, $headerMap, 'id_type');
        $id_type = $this->normalizeIdType($id_type);
        
        // Récupérer et normaliser les dates
        $date_of_birth = $this->getColumnValue($row, $headerMap, 'date_of_birth');
        $date_of_birth = $this->normalizeDate($date_of_birth);
        
        $id_expiry_date = $this->getColumnValue($row, $headerMap, 'id_expiry_date');
        $id_expiry_date = $this->normalizeDate($id_expiry_date);
        
        // Récupérer et normaliser les numéros de téléphone
        $phone = $this->getColumnValue($row, $headerMap, 'phone');
        $phone = $this->normalizePhoneNumber($phone);
        
        $autre_contact = $this->getColumnValue($row, $headerMap, 'autre_contact');
        $autre_contact = $this->normalizePhoneNumber($autre_contact);
        
        // Récupérer et normaliser l'état du support
        $etat_support = $this->getColumnValue($row, $headerMap, 'etat_support');
        $etat_support = $this->normalizeEtatSupport($etat_support);

        return [
            'organization_id' => $organizationId,
            'nom_point' => $this->getColumnValue($row, $headerMap, 'nom_point'),
            'numero_flooz' => $this->getColumnValue($row, $headerMap, 'numero_flooz'),
            'shortcode' => $this->getColumnValue($row, $headerMap, 'shortcode'),
            'profil' => $profil ?: 'DISTRO', // Valeur par défaut
            'region' => strtoupper($this->getColumnValue($row, $headerMap, 'region')),
            'prefecture' => $prefecture,
            'commune' => $commune,
            'ville' => $ville,
            'quartier' => $quartier,
            'canton' => $canton,
            'dealer_name' => $this->getColumnValue($row, $headerMap, 'dealer_name') ?: 'Importé',
            'latitude' => $this->getColumnValue($row, $headerMap, 'latitude') ?: 0,
            'longitude' => $this->getColumnValue($row, $headerMap, 'longitude') ?: 0,
            // Informations gérant
            'firstname' => $this->getColumnValue($row, $headerMap, 'firstname'),
            'lastname' => $this->getColumnValue($row, $headerMap, 'lastname'),
            'gender' => $gender,
            'sexe_gerant' => $sexe_gerant,
            'date_of_birth' => $date_of_birth,
            // Documents
            'id_description' => $id_type,
            'id_number' => $this->getColumnValue($row, $headerMap, 'id_number'),
            'id_expiry_date' => $id_expiry,
            'nationality' => $this->getColumnValue($row, $headerMap, 'nationality'),
            'profession' => $this->getColumnValue($row, $headerMap, 'profession'),
            'type_activite' => $this->getColumnValue($row, $headerMap, 'type_activite'),
            // Fiscalité
            'nif' => $nif,
            'regime_fiscal' => $regime_fiscal,
            // Contacts
            'numero_proprietaire' => $phone,
            'autre_contact' => $autre_contact,
            // Visibilité
            'support_visibilite' => $this->getColumnValue($row, $headerMap, 'support_visibilite'),
            'etat_support' => $etat_support,
            'numero_cagnt' => $this->getColumnValue($row, $headerMap, 'numero_cagnt'),
            // Status
            'status' => 'validated',
            'created_by' => $user->id,
            'validated_by' => $user->id,
            'validated_at' => now(),
        ];
    }
    
    /**
     * Normaliser la commune (et autres champs géographiques)
     * Transforme "Ogou 1" en "Ogou_1"
     */
    private function normalizeCommune($commune)
    {
        if (!$commune) return null;
        
        $commune = trim($commune);
        
        // Transformer les espaces suivis de chiffres en underscores
        // Ogou 1 -> Ogou_1, Akébou 2 -> Akébou_2
        $commune = preg_replace('/ (\d+)$/', '_$1', $commune);
        
        return $commune;
    }
    
    /**
     * Normaliser un numéro de téléphone
     * Ajoute 228 si le numéro a 8 chiffres
     */
    private function normalizePhoneNumber($phone)
    {
        if (!$phone) return null;
        
        // Nettoyer le numéro (garder uniquement les chiffres)
        $phone = preg_replace('/[^0-9]/', '', trim($phone));
        
        // Si le numéro a exactement 8 chiffres, ajouter 228 devant
        if (strlen($phone) === 8) {
            $phone = '228' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Normaliser le sexe/gender
     */
    private function normalizeGender($gender)
    {
        if (!$gender) return null;
        
        $gender = strtoupper(trim($gender));
        
        // Mapping des variantes
        $genderMap = [
            'MASCULIN' => 'M',
            'M' => 'M',
            'MALE' => 'M',
            'HOMME' => 'M',
            'FEMININ' => 'F',
            'FÉMININ' => 'F',
            'F' => 'F',
            'FEMALE' => 'F',
            'FEMME' => 'F',
        ];
        
        return $genderMap[$gender] ?? null;
    }
    
    /**
     * Normaliser le NIF
     */
    private function normalizeNIF($nif)
    {
        if (!$nif) return null;
        
        $nif = strtoupper(trim($nif));
        
        // Si c'est "SANS NIF" ou similaire, retourner null
        if (in_array($nif, ['SANS NIF', 'SANS-NIF', 'SANSNIF', 'SANS', 'N/A', 'NA', ''])) {
            return null;
        }
        
        return $nif;
    }
    
    /**
     * Normaliser le régime fiscal
     */
    private function normalizeRegimeFiscal($regime)
    {
        if (!$regime) return null;
        
        $regime = trim($regime);
        $regimeUpper = strtoupper($regime);
        
        // Si c'est "SANS" ou similaire, retourner null
        if (in_array($regimeUpper, ['SANS NIF', 'SANS', 'N/A', 'NA', ''])) {
            return null;
        }
        
        // Transformer "Sans TVA" en "Réel sans TVA" et "Avec TVA" en "Réel avec TVA"
        $mapping = [
            'SANS TVA' => 'Réel sans TVA',
            'AVEC TVA' => 'Réel avec TVA',
            'REEL SANS TVA' => 'Réel sans TVA',
            'RÉEL SANS TVA' => 'Réel sans TVA',
            'REEL AVEC TVA' => 'Réel avec TVA',
            'RÉEL AVEC TVA' => 'Réel avec TVA',
        ];
        
        return $mapping[$regimeUpper] ?? $regime;
    }
    
    /**
     * Normaliser le type de pièce
     */
    private function normalizeIdType($idType)
    {
        if (!$idType) return null;
        
        $idType = strtoupper(trim($idType));
        
        // Mapping des types de pièces
        $mapping = [
            'CARTE CONSULAIRE' => 'foreign_id',
            'CARTE D\'ELECTEUR' => 'elector',
            'CARTE D ELECTEUR' => 'elector',
            'CARTE D\'IDENTITE NATIONALE' => 'cni',
            'CARTE D IDENTITE NATIONALE' => 'cni',
            'CARTE NATIONALE D\'IDENTITE' => 'cni',
            'CARTE NATIONALE D IDENTITE' => 'cni',
            'CNI' => 'cni',
            'PERMIS DE CONDUIRE' => 'driving_license',
            'PASSEPORT' => 'passport',
            'CARTE DE SEJOUR' => 'residence',
            'CARTE ANID' => 'anid_card',
        ];
        
        return $mapping[$idType] ?? $idType;
    }
    
    /**
     * Normaliser une date
     */
    private function normalizeDate($date)
    {
        if (!$date) return null;
        
        $date = trim($date);
        if (empty($date)) return null;
        
        try {
            // Essayer différents formats de date
            $formats = [
                'Y-m-d',      // 2030-12-10
                'd/m/Y',      // 10/12/2030
                'd-m-Y',      // 10-12-2030
                'm/d/Y',      // 12/10/2030 (US format)
                'Y/m/d',      // 2030/12/10
                'd.m.Y',      // 10.12.2030
            ];
            
            foreach ($formats as $format) {
                $dateObj = \DateTime::createFromFormat($format, $date);
                if ($dateObj !== false && $dateObj->format($format) === $date) {
                    return $dateObj->format('Y-m-d');
                }
            }
            
            // Si aucun format ne fonctionne, essayer strtotime
            $timestamp = strtotime($date);
            if ($timestamp !== false) {
                return date('Y-m-d', $timestamp);
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Normaliser l'état du support
     */
    private function normalizeEtatSupport($etat)
    {
        if (!$etat) return null;
        
        $etat = strtoupper(trim($etat));
        
        // Mapping des états
        $mapping = [
            'BON' => 'BON',
            'MAUVAIS' => 'MAUVAIS',
            'DEFRAICHI' => 'DEFRAICHI',
            'DÉFRAICHI' => 'DEFRAICHI',
            'DEFRACHI' => 'DEFRAICHI',
            'ABIME' => 'MAUVAIS',
            'ABÎME' => 'MAUVAIS',
            'ABIMÉ' => 'MAUVAIS',
        ];
        
        return $mapping[$etat] ?? $etat;
    }

    /**
     * Obtenir la valeur d'une colonne
     */
    private function getColumnValue($row, $headerMap, $columnName)
    {
        $index = $headerMap[$columnName] ?? null;
        if ($index === null) {
            return null;
        }
        $value = isset($row[$index]) ? trim($row[$index]) : null;
        // Convertir les valeurs vides en null
        return ($value === '' || $value === null) ? null : $value;
    }

    /**
     * Valider une ligne de données
     */
    private function validateRow($data, $lineNumber)
    {
        return Validator::make($data, [
            'organization_id' => 'required|exists:organizations,id',
            'nom_point' => 'required|string|max:255',
            'numero_flooz' => 'required|string|size:11|regex:/^[0-9]{11}$/',
            'shortcode' => 'nullable|string|max:50',
            'profil' => 'nullable|in:DISTRO,AGNT,DISTROWNIF,DISTROTC,BANKAGNT,FTAGNT',
            'region' => 'required|in:MARITIME,PLATEAUX,CENTRALE,KARA,SAVANES',
            'prefecture' => 'nullable|string|max:255',
            'commune' => 'nullable|string|max:255',
            'ville' => 'nullable|string|max:255',
            'quartier' => 'nullable|string|max:255',
            'dealer_name' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ], [
            'numero_flooz.regex' => 'Le numéro Flooz doit contenir exactement 11 chiffres',
            'numero_flooz.size' => 'Le numéro Flooz doit contenir exactement 11 chiffres',
            'region.required' => 'La région est obligatoire',
            'region.in' => 'La région doit être: MARITIME, PLATEAUX, CENTRALE, KARA ou SAVANES',
            'profil.in' => 'Le profil doit être: DISTRO, AGNT, DISTROWNIF, DISTROTC, BANKAGNT ou FTAGNT',
        ]);
    }

    /**
     * Télécharger un modèle Excel
     */
    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // En-têtes
        $headers = [
            'nom_point', 'numero_flooz', 'shortcode', 'profil', 'region', 'prefecture',
            'commune', 'ville', 'quartier', 'dealer_name', 'latitude', 'longitude'
        ];

        $sheet->fromArray([$headers], null, 'A1');

        // Exemple de ligne
        $exampleRow = [
            'Boutique Test',
            '9012345678',
            'TEST01',
            'PSDTT',
            'MARITIME',
            'Golfe',
            'Golfe_1',
            'Lomé',
            'Tokoin',
            'John Doe',
            '6.1319',
            '1.2223'
        ];
        $sheet->fromArray([$exampleRow], null, 'A2');

        // Style pour les en-têtes
        $sheet->getStyle('A1:L1')->getFont()->setBold(true);
        $sheet->getStyle('A1:L1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FF6B00');

        // Auto-size columns
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        
        $filename = 'modele_import_pdv.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($temp_file);

        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }
}
