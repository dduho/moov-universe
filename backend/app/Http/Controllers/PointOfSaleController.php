<?php

namespace App\Http\Controllers;

use App\Models\PointOfSale;
use App\Services\ProximityAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PointOfSaleController extends Controller
{
    protected $proximityService;

    public function __construct(ProximityAlertService $proximityService)
    {
        $this->proximityService = $proximityService;
    }

    /**
     * Applique tous les filtres de recherche à une requête
     * Réutilisable par index() et exportAll()
     */
    private function applyFilters($query, Request $request, $user): void
    {
        // Apply basic filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('region')) {
            $query->where('region', $request->region);
        }
        if ($request->has('prefecture')) {
            $query->where('prefecture', $request->prefecture);
        }
        if ($request->has('commune')) {
            $query->where('commune', $request->commune);
        }
        if ($request->has('ville')) {
            $query->where('ville', 'like', '%' . $request->ville . '%');
        }
        if ($request->has('quartier')) {
            $query->where('quartier', 'like', '%' . $request->quartier . '%');
        }
        if ($request->has('organization_id') && $user->isAdmin()) {
            $query->where('organization_id', $request->organization_id);
        }
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom_point', 'like', "%{$search}%")
                  ->orWhere('numero_flooz', 'like', "%{$search}%")
                  ->orWhere('shortcode', 'like', "%{$search}%")
                  ->orWhere('numero_proprietaire', 'like', "%{$search}%");
            });
        }

        // Filtres de qualité des données
        if ($request->has('incomplete_data') && $request->incomplete_data) {
            $query->where(function ($q) {
                $q->whereNull('latitude')
                  ->orWhereNull('longitude')
                  ->orWhereNull('region')
                  ->orWhereNull('prefecture')
                  ->orWhereNull('commune')
                  ->orWhere('latitude', 0)
                  ->orWhere('longitude', 0);
            });
        }
        
        if ($request->has('no_gps') && $request->no_gps) {
            $query->where(function ($q) {
                $q->whereNull('latitude')
                  ->orWhereNull('longitude')
                  ->orWhere('latitude', 0)
                  ->orWhere('longitude', 0);
            });
        }

        if ($request->has('geo_inconsistency') && $request->geo_inconsistency) {
            // PDV avec incohérences géographiques
            $geoService = new \App\Services\GeoValidationService();
            
            // Récupérer les IDs des PDV avec incohérences
            $pdvsToCheck = PointOfSale::query()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->whereNotNull('region')
                ->where('latitude', '!=', '')
                ->where('longitude', '!=', '')
                ->select('id', 'latitude', 'longitude', 'region')
                ->get();
            
            $pdvIdsWithAlert = [];
            foreach ($pdvsToCheck as $pdv) {
                try {
                    $validation = $geoService->validateRegionCoordinates(
                        (float) $pdv->latitude,
                        (float) $pdv->longitude,
                        $pdv->region
                    );
                    
                    if (isset($validation['has_alert']) && $validation['has_alert']) {
                        $pdvIdsWithAlert[] = $pdv->id;
                    }
                } catch (\Exception $e) {
                    \Log::warning('Geo validation error for PDV ' . $pdv->id . ': ' . $e->getMessage());
                    continue;
                }
            }
            
            if (!empty($pdvIdsWithAlert)) {
                $query->whereIn('id', $pdvIdsWithAlert);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->has('proximity_alert') && $request->proximity_alert) {
            // PDV trop proches d'autres PDV
            try {
                $organizationId = $request->organization_id ?? null;
                $proximityData = $this->proximityService->getAllProximityAlerts($user, $organizationId);
                
                $pdvIdsWithAlert = [];
                if (!empty($proximityData['clusters'])) {
                    foreach ($proximityData['clusters'] as $cluster) {
                        if (isset($cluster['pdvs']) && is_array($cluster['pdvs'])) {
                            foreach ($cluster['pdvs'] as $pdv) {
                                if (isset($pdv['id'])) {
                                    $pdvIdsWithAlert[] = $pdv['id'];
                                }
                            }
                        }
                    }
                }
                
                if (!empty($pdvIdsWithAlert)) {
                    $query->whereIn('id', array_unique($pdvIdsWithAlert));
                } else {
                    $query->whereRaw('1 = 0');
                }
            } catch (\Exception $e) {
                \Log::error('Error filtering proximity alerts: ' . $e->getMessage());
                // En cas d'erreur, ne pas filtrer (retourner tous)
            }
        }
    }

    private function flushPdvCache(): void
    {
        try {
            // Predis/Redis supporte les tags, on utilise uniquement cette méthode
            Cache::tags(['pdv-index'])->flush();
        } catch (\Throwable $e) {
            // Si les tags ne sont pas supportés, on ne fait rien
            // plutôt que d'appeler Cache::flush() qui est désactivé en production
            \Log::warning('PDV cache flush failed (tags not supported or Redis command disabled): ' . $e->getMessage());
        }
    }

    /**
     * Export all PDVs to formatted Excel file
     * Returns Excel file with styling similar to fraud detection export
     */
    public function exportAll(Request $request)
    {
        $user = $request->user();
        
        // S'assurer que la relation role est chargée
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }
        
        // Charger toutes les colonnes nécessaires pour l'export
        $query = PointOfSale::with(['organization:id,name', 'creator:id,name']);
        
        // Filter based on user role (same as index)
        if ($user->isAdmin()) {
            // Admins see all PDV
        } elseif ($user->isDealerOwner()) {
            $query->where('organization_id', $user->organization_id);
        } elseif ($user->isCommercial()) {
            $query->where(function($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhereHas('tasks', function($taskQuery) use ($user) {
                      $taskQuery->where('assigned_to', $user->id);
                  });
            });
        } elseif ($user->isDealerAgent()) {
            $query->where('created_by', $user->id);
        } else {
            $query->whereRaw('1 = 0');
        }

        // Apply same filters as index
        $this->applyFilters($query, $request, $user);
        if ($request->has('proximity_alert') && $request->proximity_alert) {
            $query->whereHas('proximityAlerts');
        }

        // Get all PDVs (no pagination, limit to 50k for safety)
        $pdvs = $query->orderBy('created_at', 'desc')->limit(50000)->get();

        // Create Excel file with formatting
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Points de Vente');

        // Define headers
        $headers = [
            'A1' => 'Dealer',
            'B1' => 'Numéro Flooz',
            'C1' => 'Shortcode',
            'D1' => 'Nom du Point',
            'E1' => 'Profil',
            'F1' => 'Prénom Gérant',
            'G1' => 'Nom Gérant',
            'H1' => 'Genre',
            'I1' => 'Type de Pièce',
            'J1' => 'Numéro Pièce',
            'K1' => 'Expiration Pièce',
            'L1' => 'Nationalité',
            'M1' => 'Profession',
            'N1' => 'Type d\'Activité',
            'O1' => 'Région',
            'P1' => 'Préfecture',
            'Q1' => 'Commune',
            'R1' => 'Canton',
            'S1' => 'Quartier',
            'T1' => 'Ville',
            'U1' => 'Latitude',
            'V1' => 'Longitude',
            'W1' => 'Téléphone Propriétaire',
            'X1' => 'Autre Contact',
            'Y1' => 'NIF',
            'Z1' => 'Régime Fiscal',
            'AA1' => 'Support Visibilité',
            'AB1' => 'État Support',
            'AC1' => 'Numéro CAGNT',
            'AD1' => 'Statut',
            'AE1' => 'Date de Création',
            'AF1' => 'Créé par'
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
                'startColor' => ['rgb' => 'FF6B35'] // Moov Orange
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
        $sheet->getStyle('A1:AF1')->applyFromArray($headerStyle);

        // Set row height for header
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Fill data
        $row = 2;
        foreach ($pdvs as $pdv) {
            $statusLabels = [
                'pending' => 'En attente',
                'validated' => 'Validé',
                'rejected' => 'Rejeté'
            ];

            $sheet->setCellValue('A' . $row, $pdv->organization->name ?? '');
            $sheet->setCellValue('B' . $row, $pdv->numero_flooz ?? '');
            $sheet->setCellValue('C' . $row, $pdv->shortcode ?? '');
            $sheet->setCellValue('D' . $row, $pdv->nom_point ?? '');
            $sheet->setCellValue('E' . $row, $pdv->profil ?? '');
            $sheet->setCellValue('F' . $row, $pdv->firstname ?? '');
            $sheet->setCellValue('G' . $row, $pdv->lastname ?? '');
            $sheet->setCellValue('H' . $row, $pdv->gender ?? '');
            $sheet->setCellValue('I' . $row, $pdv->id_description ?? '');
            $sheet->setCellValue('J' . $row, $pdv->id_number ?? '');
            $sheet->setCellValue('K' . $row, $pdv->id_expiry_date ?? '');
            $sheet->setCellValue('L' . $row, $pdv->nationality ?? '');
            $sheet->setCellValue('M' . $row, $pdv->profession ?? '');
            $sheet->setCellValue('N' . $row, $pdv->type_activite ?? '');
            $sheet->setCellValue('O' . $row, $pdv->region ?? '');
            $sheet->setCellValue('P' . $row, $pdv->prefecture ?? '');
            $sheet->setCellValue('Q' . $row, $pdv->commune ?? '');
            $sheet->setCellValue('R' . $row, $pdv->canton ?? '');
            $sheet->setCellValue('S' . $row, $pdv->quartier ?? '');
            $sheet->setCellValue('T' . $row, $pdv->ville ?? '');
            $sheet->setCellValue('U' . $row, $pdv->latitude ?? '');
            $sheet->setCellValue('V' . $row, $pdv->longitude ?? '');
            $sheet->setCellValue('W' . $row, $pdv->numero_proprietaire ?? '');
            $sheet->setCellValue('X' . $row, $pdv->autre_contact ?? '');
            $sheet->setCellValue('Y' . $row, $pdv->nif ?? '');
            $sheet->setCellValue('Z' . $row, $pdv->regime_fiscal ?? '');
            $sheet->setCellValue('AA' . $row, $pdv->support_visibilite ?? '');
            $sheet->setCellValue('AB' . $row, $pdv->etat_support ?? '');
            $sheet->setCellValue('AC' . $row, $pdv->numero_cagnt ?? '');
            $sheet->setCellValue('AD' . $row, $statusLabels[$pdv->status] ?? $pdv->status);
            $sheet->setCellValue('AE' . $row, $pdv->created_at ? $pdv->created_at->format('d/m/Y H:i') : '');
            $sheet->setCellValue('AF' . $row, $pdv->creator->name ?? '');

            // Apply row styling
            $rowStyle = [
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_TOP,
                    'wrapText' => false
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ]
            ];

            $sheet->getStyle('A' . $row . ':AF' . $row)->applyFromArray($rowStyle);

            // Color code status
            $statusColors = [
                'pending' => 'FEF3C7',    // Yellow-100
                'validated' => 'D1FAE5',  // Green-100
                'rejected' => 'FEE2E2'    // Red-100
            ];

            if (isset($statusColors[$pdv->status])) {
                $sheet->getStyle('AD' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $statusColors[$pdv->status]]
                    ],
                    'font' => ['bold' => true]
                ]);
            }

            $row++;
        }

        // Set specific column widths
        $sheet->getColumnDimension('A')->setWidth(20);  // Dealer
        $sheet->getColumnDimension('B')->setWidth(15);  // Numéro Flooz
        $sheet->getColumnDimension('C')->setWidth(12);  // Shortcode
        $sheet->getColumnDimension('D')->setWidth(30);  // Nom du Point
        $sheet->getColumnDimension('E')->setWidth(15);  // Profil
        $sheet->getColumnDimension('F')->setWidth(15);  // Prénom
        $sheet->getColumnDimension('G')->setWidth(15);  // Nom
        $sheet->getColumnDimension('H')->setWidth(10);  // Genre
        $sheet->getColumnDimension('I')->setWidth(15);  // Type Pièce
        $sheet->getColumnDimension('J')->setWidth(15);  // Num Pièce
        $sheet->getColumnDimension('K')->setWidth(15);  // Expiration
        $sheet->getColumnDimension('L')->setWidth(15);  // Nationalité
        $sheet->getColumnDimension('M')->setWidth(20);  // Profession
        $sheet->getColumnDimension('N')->setWidth(20);  // Type Activité
        $sheet->getColumnDimension('O')->setWidth(15);  // Région
        $sheet->getColumnDimension('P')->setWidth(15);  // Préfecture
        $sheet->getColumnDimension('Q')->setWidth(15);  // Commune
        $sheet->getColumnDimension('R')->setWidth(15);  // Canton
        $sheet->getColumnDimension('S')->setWidth(15);  // Quartier
        $sheet->getColumnDimension('T')->setWidth(15);  // Ville
        $sheet->getColumnDimension('U')->setWidth(12);  // Latitude
        $sheet->getColumnDimension('V')->setWidth(12);  // Longitude
        $sheet->getColumnDimension('W')->setWidth(15);  // Tel Proprio
        $sheet->getColumnDimension('X')->setWidth(15);  // Autre Contact
        $sheet->getColumnDimension('Y')->setWidth(15);  // NIF
        $sheet->getColumnDimension('Z')->setWidth(15);  // Régime Fiscal
        $sheet->getColumnDimension('AA')->setWidth(20); // Support Visibilité
        $sheet->getColumnDimension('AB')->setWidth(15); // État Support
        $sheet->getColumnDimension('AC')->setWidth(15); // Numéro CAGNT
        $sheet->getColumnDimension('AD')->setWidth(12); // Statut
        $sheet->getColumnDimension('AE')->setWidth(18); // Date Création
        $sheet->getColumnDimension('AF')->setWidth(20); // Créé par

        // Generate file
        $writer = new Xlsx($spreadsheet);
        $filename = 'pdv_export_' . date('Ymd_His') . '.xlsx';
        
        // Create temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'pdv_export_');
        $writer->save($tempFile);

        // Return file as download
        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        
        // S'assurer que la relation role est chargée
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }
        
        // Ne charger que les colonnes nécessaires et relations essentielles
        $query = PointOfSale::select([
            'id', 'organization_id', 'nom_point', 'numero_flooz', 'shortcode',
            'profil', 'region', 'prefecture', 'commune', 'ville', 'quartier',
            'status', 'created_by', 'validated_by', 'created_at', 'updated_at',
            'latitude', 'longitude', 'canton',
            // Informations gérant
            'firstname', 'lastname', 'gender', 'sexe_gerant', 'date_of_birth',
            // Documents
            'id_description', 'id_number', 'id_expiry_date', 'nationality', 'profession',
            // Fiscalité
            'nif', 'regime_fiscal',
            // Contacts
            'numero_proprietaire', 'autre_contact',
            // Visibilité et autres
            'support_visibilite', 'etat_support', 'numero_cagnt', 'type_activite', 'localisation'
        ])->with(['organization:id,name', 'creator:id,name']);
        
        // Ne pas charger validator et uploads dans la liste (trop lourd)

        // Filter based on user role
        if ($user->isAdmin()) {
            // Admins see all PDV
        } elseif ($user->isDealerOwner()) {
            // Dealer owners see all PDV in their organization
            $query->where('organization_id', $user->organization_id);
        } elseif ($user->isCommercial()) {
            // Commercials see only their own PDV + PDV with tasks assigned to them
            $query->where(function($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhereHas('tasks', function($taskQuery) use ($user) {
                      $taskQuery->where('assigned_to', $user->id);
                  });
            });
        } elseif ($user->isDealerAgent()) {
            // Dealer agents see only their own PDV
            $query->where('created_by', $user->id);
        } else {
            // No access for other roles
            $query->whereRaw('1 = 0');
        }

        // Apply filters (utilise la même logique que exportAll)
        $this->applyFilters($query, $request, $user);

        // Pagination with performance optimization (cacheable)
        $perPage = $request->get('per_page', 50); // Default to 50 for better performance
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Map frontend column names to actual database column names
        $columnMap = [
            'point_name' => 'nom_point',
            'flooz_number' => 'numero_flooz',
            'owner_phone' => 'numero_proprietaire',
            'manager_firstname' => 'firstname',
            'manager_lastname' => 'lastname',
        ];
        
        // Apply column mapping if exists
        if (isset($columnMap[$sortBy])) {
            $sortBy = $columnMap[$sortBy];
        }
        
        // Validate sort column to prevent SQL injection
        $allowedColumns = [
            'id', 'nom_point', 'numero_flooz', 'shortcode', 'status', 
            'region', 'prefecture', 'commune', 'ville', 'quartier',
            'firstname', 'lastname', 'numero_proprietaire', 'created_at', 'updated_at'
        ];
        
        if (!in_array($sortBy, $allowedColumns)) {
            $sortBy = 'created_at';
        }
        
        // Limiter le per_page max à 100 pour éviter les surcharges
        if ($perPage > 100) {
            $perPage = 100;
        }
        
        // Cache layer: vary by user/org and query string to avoid cross-tenant leaks
        $cacheKeyParts = [
            'pdv-index',
            'user:' . $user->id,
            'org:' . ($user->organization_id ?? 'none'),
            'role:' . ($user->role->name ?? 'none'),
            'params:' . md5(json_encode($request->all()))
        ];
        $cacheKey = implode('|', $cacheKeyParts);

        $ttlSeconds = 300; // 5 minutes

        $cache = cache();

        if (method_exists($cache, 'tags')) {
            $paginator = $cache->tags(['pdv-index'])->remember($cacheKey, $ttlSeconds, function () use ($query, $sortBy, $sortOrder, $perPage) {
                return $query->orderBy($sortBy, $sortOrder)->paginate($perPage);
            });
        } else {
            $paginator = $cache->remember($cacheKey, $ttlSeconds, function () use ($query, $sortBy, $sortOrder, $perPage) {
                return $query->orderBy($sortBy, $sortOrder)->paginate($perPage);
            });
        }

        return response()->json($paginator);
    }

    /**
     * Get all points of sale for map view (optimized, no pagination)
     * Returns only essential fields for markers
     */
    public function forMap(Request $request)
    {
        $user = $request->user();
        
        // S'assurer que la relation role est chargée
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }

        // Check cache settings
        $cacheEnabled = \App\Models\SystemSetting::getValue('cache_map_enabled', true);
        $cacheTtl = (int) \App\Models\SystemSetting::getValue('cache_map_ttl', 30);

        // Create cache key based on user role and filters
        $cacheKey = 'map_data_' . $user->id . '_' . md5(json_encode([
            'role' => $user->role->name ?? 'unknown',
            'org_id' => $user->organization_id,
            'status' => $request->get('status'),
            'region' => $request->get('region'),
            'organization_id' => $request->get('organization_id'),
        ]));

        // Try to get from cache if enabled
        if ($cacheEnabled) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return response()->json($cached);
            }
        }
        
        // Sélectionner uniquement les champs nécessaires pour la carte
        $query = PointOfSale::select([
            'id', 'organization_id', 'nom_point', 'numero_flooz', 'shortcode',
            'profil', 'region', 'prefecture', 'ville', 'quartier',
            'status', 'latitude', 'longitude'
        ])->with(['organization:id,name']);

        // Filter based on user role
        if ($user->isAdmin()) {
            // Admins see all PDV
        } elseif ($user->isDealerOwner()) {
            $query->where('organization_id', $user->organization_id);
        } elseif ($user->isCommercial()) {
            $query->where(function($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhereHas('tasks', function($taskQuery) use ($user) {
                      $taskQuery->where('assigned_to', $user->id);
                  });
            });
        } elseif ($user->isDealerAgent()) {
            $query->where('created_by', $user->id);
        } else {
            $query->whereRaw('1 = 0');
        }

        // Apply filters
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('region') && $request->region) {
            $query->where('region', $request->region);
        }

        if ($request->has('organization_id') && $request->organization_id) {
            $query->where('organization_id', $request->organization_id);
        }

        // Exclure les PDV sans coordonnées GPS valides
        $query->whereNotNull('latitude')
              ->whereNotNull('longitude')
              ->where('latitude', '!=', 0)
              ->where('longitude', '!=', 0);

        // Retourner tous les résultats sans pagination
        $result = $query->get();

        // Store in cache if enabled
        if ($cacheEnabled) {
            Cache::put($cacheKey, $result, $cacheTtl * 60); // Convert minutes to seconds
        }

        return response()->json($result);
    }

    public function store(Request $request)
    {
        if ($request->has('support_visibilite')) {
            $request->merge([
                'support_visibilite' => $this->normalizeSupportVisibilite($request->input('support_visibilite')),
            ]);
        }

        if ($request->has('etat_support') && $request->filled('etat_support')) {
            $request->merge([
                'etat_support' => strtoupper($request->input('etat_support')),
            ]);
        }

        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'numero_flooz' => 'required|string|unique:point_of_sales,numero_flooz',
            'shortcode' => 'required|string|unique:point_of_sales,shortcode',
            'nom_point' => 'required|string',
            'profil' => 'required|string',
            'type_activite' => 'nullable|string',
            'firstname' => 'nullable|string',
            'lastname' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:M,F',
            'id_description' => 'nullable|string',
            'id_number' => 'nullable|string',
            'id_expiry_date' => 'nullable|date',
            'nationality' => 'nullable|string',
            'profession' => 'nullable|string',
            'sexe_gerant' => 'nullable|in:M,F',
            'region' => 'required|in:MARITIME,PLATEAUX,CENTRALE,KARA,SAVANES',
            'prefecture' => 'required|string',
            'commune' => 'required|string',
            'canton' => 'nullable|string',
            'ville' => 'required|string',
            'quartier' => 'required|string',
            'localisation' => 'nullable|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'gps_accuracy' => 'nullable|numeric',
            'numero_proprietaire' => 'required|string',
            'autre_contact' => 'nullable|string',
            'nif' => 'nullable|string',
            'regime_fiscal' => 'nullable|string',
            'support_visibilite' => 'nullable|string',
            'etat_support' => 'nullable|in:BON,ACCEPTABLE,MAUVAIS,DEFRAICHI',
            'numero_cagnt' => 'required|string',
        ], [
            'numero_flooz.unique' => 'Ce numéro Flooz est déjà utilisé par un autre point de vente.',
            'shortcode.unique' => 'Ce shortcode est déjà utilisé par un autre point de vente.',
            'organization_id.required' => 'L\'organisation est obligatoire.',
            'organization_id.exists' => 'L\'organisation sélectionnée n\'existe pas.',
            'numero_flooz.required' => 'Le numéro Flooz est obligatoire.',
            'shortcode.required' => 'Le shortcode est obligatoire.',
            'nom_point.required' => 'Le nom du point de vente est obligatoire.',
            'profil.required' => 'Le profil est obligatoire.',
            'region.required' => 'La région est obligatoire.',
            'region.in' => 'La région sélectionnée n\'est pas valide.',
            'prefecture.required' => 'La préfecture est obligatoire.',
            'commune.required' => 'La commune est obligatoire.',
            'ville.required' => 'La ville est obligatoire.',
            'quartier.required' => 'Le quartier est obligatoire.',
            'latitude.required' => 'La latitude est obligatoire.',
            'latitude.numeric' => 'La latitude doit être un nombre.',
            'latitude.between' => 'La latitude doit être comprise entre -90 et 90.',
            'longitude.required' => 'La longitude est obligatoire.',
            'longitude.numeric' => 'La longitude doit être un nombre.',
            'longitude.between' => 'La longitude doit être comprise entre -180 et 180.',
            'numero_proprietaire.required' => 'Le numéro du propriétaire est obligatoire.',
            'support_visibilite.required' => 'Le support de visibilité est obligatoire.',
            'numero_cagnt.required' => 'Le numéro CAGNT est obligatoire.',
        ]);

        $user = $request->user();

        // Check proximity
        $proximityCheck = $this->proximityService->checkProximity(
            $validated['latitude'],
            $validated['longitude']
        );

        // For non-admin users, force their organization_id
        if (!$user->isAdmin()) {
            $validated['organization_id'] = $user->organization_id;
        }
        
        $validated['created_by'] = $user->id;
        $validated['status'] = 'pending';

        $pdv = PointOfSale::create($validated);

        $this->flushPdvCache();

        // Attach uploaded files
        if ($request->has('owner_id_document_ids')) {
            foreach ($request->owner_id_document_ids as $uploadId) {
                $pdv->uploads()->create([
                    'upload_id' => $uploadId,
                    'file_path' => 'uploads/id_documents/' . $uploadId,
                    'type' => 'id_document',
                ]);
            }
        }

        if ($request->has('photo_ids')) {
            foreach ($request->photo_ids as $uploadId) {
                $pdv->uploads()->create([
                    'upload_id' => $uploadId,
                    'file_path' => 'uploads/photos/' . $uploadId,
                    'type' => 'photo',
                ]);
            }
        }

        if ($request->has('fiscal_document_ids')) {
            foreach ($request->fiscal_document_ids as $uploadId) {
                $pdv->uploads()->create([
                    'upload_id' => $uploadId,
                    'file_path' => 'uploads/fiscal_documents/' . $uploadId,
                    'type' => 'fiscal_document',
                ]);
            }
        }

        return response()->json([
            'pdv' => $pdv->load(['organization', 'creator', 'idDocuments', 'photos', 'fiscalDocuments']),
            'proximity_alert' => $proximityCheck,
        ], 201);
    }

    public function show($id, Request $request)
    {
        $user = $request->user();
        $query = PointOfSale::with(['organization', 'creator', 'validator', 'idDocuments', 'photos', 'fiscalDocuments', 'tasks']);

        $pdv = $query->findOrFail($id);

        // Check access permissions
        if (!$user->canAccessPointOfSale($pdv)) {
            return response()->json(['message' => 'Forbidden - You do not have access to this PDV'], 403);
        }

        // has_active_task est maintenant calculé automatiquement via l'accessor du modèle

        // Check proximity if PDV has coordinates
        $proximityCheck = null;
        if ($pdv->latitude && $pdv->longitude) {
            $proximityCheck = $this->proximityService->checkProximity(
                $pdv->latitude,
                $pdv->longitude,
                $pdv->id
            );
        }

        return response()->json([
            'pdv' => $pdv,
            'proximity_alert' => $proximityCheck,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $pdv = PointOfSale::findOrFail($id);

        // Check access permissions
        if (!$user->canAccessPointOfSale($pdv)) {
            return response()->json(['message' => 'Forbidden - You do not have access to this PDV'], 403);
        }

        // Logique de permission de modification:
        // - Admin: peut toujours modifier un PDV quel que soit son statut
        // - Commercial: peut modifier si PDV pending OU si une tâche est en révision demandée
        $canUpdate = false;
        
        if ($user->isAdmin()) {
            // Admin peut toujours modifier
            $canUpdate = true;
        } elseif ($pdv->status === 'pending') {
            $canUpdate = true;
        } else {
            // Commercial peut modifier seulement si une tâche est en révision demandée
            $canUpdate = $pdv->tasks()->where('status', 'revision_requested')->exists();
        }
        
        if (!$canUpdate) {
            return response()->json(['message' => 'Vous ne pouvez pas modifier ce PDV'], 422);
        }

        if ($request->has('support_visibilite')) {
            $request->merge([
                'support_visibilite' => $this->normalizeSupportVisibilite($request->input('support_visibilite')),
            ]);
        }

        if ($request->has('etat_support') && $request->filled('etat_support')) {
            $request->merge([
                'etat_support' => strtoupper($request->input('etat_support')),
            ]);
        }

        $validated = $request->validate([
            'numero_flooz' => 'sometimes|string',
            'shortcode' => 'nullable|string',
            'nom_point' => 'sometimes|string',
            'profil' => 'nullable|string',
            'type_activite' => 'nullable|string',
            'firstname' => 'sometimes|string',
            'lastname' => 'sometimes|string',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:M,F',
            'region' => 'sometimes|in:MARITIME,PLATEAUX,CENTRALE,KARA,SAVANES',
            'prefecture' => 'sometimes|string',
            'commune' => 'nullable|string',
            'ville' => 'nullable|string',
            'quartier' => 'nullable|string',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'gps_accuracy' => 'nullable|numeric',
            'support_visibilite' => 'nullable|string',
            'etat_support' => 'nullable|in:BON,ACCEPTABLE,MAUVAIS,DEFRAICHI',
            'numero_cagnt' => 'nullable|string',
            'type_activite' => 'nullable|string',
            'regime_fiscal' => 'nullable|string',
            'nif' => 'nullable|string',
            'autre_contact' => 'nullable|string',
        ]);

        $pdv->update($validated);

        // Attach uploaded files (update existing associations)
        if ($request->has('owner_id_document_ids')) {
            // Remove old associations
            $pdv->uploads()->where('type', 'id_document')->delete();
            // Add new associations
            foreach ($request->owner_id_document_ids as $uploadId) {
                $pdv->uploads()->create([
                    'upload_id' => $uploadId,
                    'file_path' => 'uploads/id_documents/' . $uploadId,
                    'type' => 'id_document',
                ]);
            }
        }

        if ($request->has('photo_ids')) {
            // Remove old associations
            $pdv->uploads()->where('type', 'photo')->delete();
            // Add new associations
            foreach ($request->photo_ids as $uploadId) {
                $pdv->uploads()->create([
                    'upload_id' => $uploadId,
                    'file_path' => 'uploads/photos/' . $uploadId,
                    'type' => 'photo',
                ]);
            }
        }

        if ($request->has('fiscal_document_ids')) {
            // Remove old associations
            $pdv->uploads()->where('type', 'fiscal_document')->delete();
            // Add new associations
            foreach ($request->fiscal_document_ids as $uploadId) {
                $pdv->uploads()->create([
                    'upload_id' => $uploadId,
                    'file_path' => 'uploads/fiscal_documents/' . $uploadId,
                    'type' => 'fiscal_document',
                ]);
            }
        }

        $this->flushPdvCache();

        return response()->json($pdv->load(['organization', 'creator', 'idDocuments', 'photos', 'fiscalDocuments']));
    }

    public function validatePdv(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Only admins can validate'], 403);
        }

        $pdv = PointOfSale::findOrFail($id);

        if ($pdv->status !== 'pending') {
            return response()->json(['message' => 'PDV is not pending'], 422);
        }

        $pdv->update([
            'status' => 'validated',
            'validated_by' => $user->id,
            'validated_at' => now(),
        ]);

        $this->flushPdvCache();

        // Notifier le créateur du PDV
        if ($pdv->creator) {
            DB::table('notifications')->insert([
                'user_id' => $pdv->creator->id,
                'type' => 'pdv_validated',
                'title' => 'PDV validé',
                'message' => 'Votre PDV "' . $pdv->nom_point . '" a été validé par ' . $user->name,
                'data' => json_encode([
                    'pdv_id' => $pdv->id,
                    'pdv_name' => $pdv->nom_point,
                    'url' => '/pdv/' . $pdv->id,
                ]),
                'read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json($pdv->load(['organization', 'creator', 'validator']));
    }

    public function reject(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Only admins can reject'], 403);
        }

        $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $pdv = PointOfSale::findOrFail($id);

        if ($pdv->status !== 'pending') {
            return response()->json(['message' => 'PDV is not pending'], 422);
        }

        $pdv->update([
            'status' => 'rejected',
            'validated_by' => $user->id,
            'rejected_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        $this->flushPdvCache();

        // Notifier le créateur du PDV
        if ($pdv->creator) {
            DB::table('notifications')->insert([
                'user_id' => $pdv->creator->id,
                'type' => 'pdv_rejected',
                'title' => 'PDV rejeté',
                'message' => 'Votre PDV "' . $pdv->nom_point . '" a été rejeté : ' . $request->rejection_reason,
                'data' => json_encode([
                    'pdv_id' => $pdv->id,
                    'pdv_name' => $pdv->nom_point,
                    'rejection_reason' => $request->rejection_reason,
                    'url' => '/pdv/' . $pdv->id,
                ]),
                'read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json($pdv->load(['organization', 'creator', 'validator']));
    }

    public function checkProximity(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'exclude_id' => 'nullable|integer',
        ]);

        $result = $this->proximityService->checkProximity(
            $request->latitude,
            $request->longitude,
            $request->exclude_id
        );

        return response()->json($result);
    }

    public function getProximityAlerts(Request $request)
    {
        $user = $request->user();
        $result = $this->proximityService->getAllProximityAlerts($user);
        
        return response()->json($result);
    }

    public function checkUniqueness(Request $request)
    {
        $request->validate([
            'field' => 'required|in:numero_flooz,shortcode,profil',
            'value' => 'required|string',
            'exclude_id' => 'nullable|integer',
        ]);

        $query = PointOfSale::where($request->field, $request->value);
        
        // Exclure le PDV en cours d'édition si un ID est fourni
        if ($request->exclude_id) {
            $query->where('id', '!=', $request->exclude_id);
        }
        
        $exists = $query->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Cette valeur est déjà utilisée' : 'Valeur disponible',
        ]);
    }

    /**
     * Clear duplicate GPS coordinates
     * When two PDVs have the same coordinates, we can't know which one is correct
     * So we set both to null
     */
    public function clearDuplicateCoordinates(Request $request)
    {
        $user = $request->user();
        
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Find all coordinates that appear more than once
        $duplicates = PointOfSale::selectRaw('ROUND(latitude, 6) as lat, ROUND(longitude, 6) as lng')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', 0)
            ->where('longitude', '!=', 0)
            ->groupByRaw('ROUND(latitude, 6), ROUND(longitude, 6)')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        
        $clearedCount = 0;
        $affectedIds = [];
        
        foreach ($duplicates as $dup) {
            // Get all PDVs with these coordinates
            $pdvs = PointOfSale::whereRaw('ROUND(latitude, 6) = ?', [$dup->lat])
                ->whereRaw('ROUND(longitude, 6) = ?', [$dup->lng])
                ->get();
            
            foreach ($pdvs as $pdv) {
                $pdv->latitude = null;
                $pdv->longitude = null;
                $pdv->save();
                $clearedCount++;
                $affectedIds[] = $pdv->id;
            }
        }
        
        return response()->json([
            'message' => "Cleared coordinates for {$clearedCount} PDVs with duplicate GPS positions",
            'cleared_count' => $clearedCount,
            'affected_ids' => $affectedIds
        ]);
    }

    /**
     * Get statistics about PDVs without valid GPS coordinates
     */
    public function getGpsStats(Request $request)
    {
        $user = $request->user();
        
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }
        
        // Base query based on role
        $baseQuery = PointOfSale::query();
        
        if ($user->isAdmin()) {
            // Admin sees all
        } elseif ($user->isDealerOwner()) {
            $baseQuery->where('organization_id', $user->organization_id);
        } elseif ($user->isCommercial()) {
            $baseQuery->where(function($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhereHas('tasks', function($taskQuery) use ($user) {
                      $taskQuery->where('assigned_to', $user->id);
                  });
            });
        } elseif ($user->isDealerAgent()) {
            $baseQuery->where('created_by', $user->id);
        } else {
            $baseQuery->whereRaw('1 = 0');
        }
        
        // Total PDVs
        $total = (clone $baseQuery)->count();
        
        // PDVs without GPS
        $withoutGps = (clone $baseQuery)
            ->where(function($q) {
                $q->whereNull('latitude')
                  ->orWhereNull('longitude')
                  ->orWhere('latitude', 0)
                  ->orWhere('longitude', 0);
            })
            ->count();
        
        // Get the list of PDVs without GPS (limited for performance)
        $pdvsWithoutGps = (clone $baseQuery)
            ->select(['id', 'nom_point', 'numero_flooz', 'region', 'ville', 'quartier', 'status', 'organization_id', 'created_by'])
            ->with(['organization:id,name', 'creator:id,name'])
            ->where(function($q) {
                $q->whereNull('latitude')
                  ->orWhereNull('longitude')
                  ->orWhere('latitude', 0)
                  ->orWhere('longitude', 0);
            })
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
        
        return response()->json([
            'total_pdv' => $total,
            'without_gps' => $withoutGps,
            'with_gps' => $total - $withoutGps,
            'percentage_without_gps' => $total > 0 ? round(($withoutGps / $total) * 100, 1) : 0,
            'pdvs_without_gps' => $pdvsWithoutGps
        ]);
    }

    public function destroy($id, Request $request)
    {
        $user = $request->user();
        $pdv = PointOfSale::findOrFail($id);

        // Only creator or admin can delete
        if (!$user->isAdmin() && $pdv->created_by !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Only allow deletion if pending
        if ($pdv->status !== 'pending') {
            return response()->json(['message' => 'Can only delete pending PDVs'], 422);
        }

        $pdv->delete();

        $this->flushPdvCache();

        return response()->json(['message' => 'PDV deleted successfully']);
    }

    private function normalizeSupportVisibilite($value): ?string
    {
        if (!$value) {
            return null;
        }

        // Si un tableau est reçu, l'aplatir en liste
        $parts = is_array($value) ? $value : [$value];
        $normalized = [];

        $synonyms = [
            'AUCUN' => null,
            'NONE' => null,
        ];

        $mapping = [
            'AUTOCOLLANT' => 'Autocollant',
            'POTENCE' => 'Potence',
            'CHEVALET' => 'Chevalet',
            'BUJET FLAGS' => 'Buget Flags',
            'BUDJET FLAGS' => 'Buget Flags',
            'BUDGET FLAGS' => 'Buget Flags',
            'PARASOLS' => 'Parasols',
            'PARASOL' => 'Parasols',
            'BEACH FLAGS' => 'Beach Flags',
            'BEACH FLAG' => 'Beach Flags',
            'ENSEIGNES LUMINEUSES' => 'Enseignes Lumineuses',
            'ENSEIGNE LUMINEUSE' => 'Enseignes Lumineuses',
            'DRAPELET' => 'Drapelet',
            'DRAPELET FLOOZ MONEY' => 'Drapelet',
            'DRAPEAU' => 'Drapelet',
        ];

        foreach ($parts as $entry) {
            if ($entry === null) {
                continue;
            }

            // Découper sur les séparateurs courants (+, virgule, point-virgule, barre, slash, "et")
            $split = preg_split('/[\+;,\|\/]+|\bet\b|\band\b/i', (string) $entry);
            foreach ($split as $rawPart) {
                $clean = trim($rawPart);
                if ($clean === '') {
                    continue;
                }

                $upper = strtoupper($clean);

                if (array_key_exists($upper, $synonyms) && $synonyms[$upper] === null) {
                    // AUCUN / NONE -> ignorer la liste entière
                    return null;
                }

                $mapped = $mapping[$upper] ?? null;
                if (!$mapped) {
                    // Formatage générique (titre)
                    $mapped = ucwords(strtolower($clean));
                }

                if (!in_array($mapped, $normalized, true)) {
                    $normalized[] = $mapped;
                }
            }
        }

        if (empty($normalized)) {
            return null;
        }

        return implode(', ', $normalized);
    }
}
