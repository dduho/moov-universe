<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointOfSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'created_by',
        'updated_by',
        'validated_by',
        'status',
        'is_locked',
        'numero',
        'dealer_name',
        'numero_flooz',
        'shortcode',
        'nom_point',
        'profil',
        'type_activite',
        'firstname',
        'lastname',
        'date_of_birth',
        'gender',
        'id_description',
        'id_number',
        'id_expiry_date',
        'nationality',
        'profession',
        'sexe_gerant',
        'region',
        'prefecture',
        'commune',
        'canton',
        'ville',
        'quartier',
        'localisation',
        'latitude',
        'longitude',
        'gps_accuracy',
        'numero_proprietaire',
        'autre_contact',
        'nif',
        'regime_fiscal',
        'support_visibilite',
        'etat_support',
        'numero_cagnt',
        'validated_at',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'id_expiry_date' => 'date',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'gps_accuracy' => 'decimal:2',
        'validated_at' => 'datetime',
        'rejected_at' => 'datetime',
        'is_locked' => 'boolean',
        'geo_has_alert' => 'boolean',
    ];

    protected $hidden = ['geo_actual_region', 'geo_has_alert'];

    /**
     * Attributs calculés ajoutés à la sérialisation du détail d'un PDV.
     * Ils ne sont PAS dans $appends : sur une liste de 25k PDV ils coûtaient
     * 2 requêtes SQL + un test point-dans-polygone par ligne.
     * Utiliser withDetailAttributes() là où le frontend en a besoin.
     */
    public const DETAIL_APPENDS = ['has_active_task', 'has_task_in_revision', 'geo_validation', 'missing_required_fields'];

    protected static function booted(): void
    {
        // Région réelle (déduite du GPS) et alerte d'incohérence stockées en base,
        // pour filtrer en SQL au lieu de recalculer les polygones à chaque requête.
        static::saving(function (PointOfSale $pdv) {
            if ($pdv->isDirty(['latitude', 'longitude', 'region']) || !$pdv->exists) {
                $pdv->fillGeoColumns();
            }
        });

        static::saved(fn () => static::bumpMapCacheVersion());
        static::deleted(fn () => static::bumpMapCacheVersion());
    }

    /**
     * Version des données carte : incluse dans les clés de cache de /for-map,
     * l'incrémenter rend toutes les anciennes entrées obsolètes (sans Cache::tags).
     */
    public static function mapCacheVersion(): int
    {
        return (int) \Illuminate\Support\Facades\Cache::get('pdv_map_version', 1);
    }

    public static function bumpMapCacheVersion(): void
    {
        try {
            \Illuminate\Support\Facades\Cache::forever('pdv_map_version', static::mapCacheVersion() + 1);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('PDV map cache version bump failed: ' . $e->getMessage());
        }
    }

    public function fillGeoColumns(): void
    {
        $validation = app(\App\Services\GeoValidationService::class)->validateRegionCoordinates(
            $this->latitude !== null ? (float) $this->latitude : null,
            $this->longitude !== null ? (float) $this->longitude : null,
            $this->region
        );

        $this->geo_actual_region = $validation['actual_region'] ?? null;
        $this->geo_has_alert = (bool) ($validation['has_alert'] ?? false);
    }

    public function withDetailAttributes(): static
    {
        return $this->append(self::DETAIL_APPENDS);
    }

    /**
     * Accessor pour la validation géographique
     * Vérifie si les coordonnées GPS correspondent à la région déclarée
     */
    public function getGeoValidationAttribute()
    {
        if (!$this->latitude || !$this->longitude || !$this->region) {
            return [
                'is_valid' => true,
                'has_alert' => false,
                'message' => null
            ];
        }

        return app(\App\Services\GeoValidationService::class)->validateRegionCoordinates(
            (float) $this->latitude,
            (float) $this->longitude,
            $this->region
        );
    }

    /**
     * Accessor pour vérifier si le PDV a une tâche active (non validée/complétée)
     * Utilisé par les admins pour voir s'il y a des tâches en cours
     */
    public function getHasActiveTaskAttribute()
    {
        if ($this->relationLoaded('tasks')) {
            return $this->tasks->contains(fn ($task) => $task->status !== 'validated');
        }

        return $this->tasks()->whereNotIn('status', ['validated'])->exists();
    }

    /**
     * Accessor pour vérifier si le PDV a une tâche en révision demandée
     * C'est le seul cas où un commercial peut modifier un PDV validé
     */
    public function getHasTaskInRevisionAttribute()
    {
        if ($this->relationLoaded('tasks')) {
            return $this->tasks->contains(fn ($task) => $task->status === 'revision_requested');
        }

        return $this->tasks()->where('status', 'revision_requested')->exists();
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function uploads()
    {
        return $this->hasMany(PointOfSaleUpload::class);
    }

    public function idDocuments()
    {
        return $this->hasMany(PointOfSaleUpload::class)->where('type', 'id_document');
    }

    public function photos()
    {
        return $this->hasMany(PointOfSaleUpload::class)->where('type', 'photo');
    }

    public function fiscalDocuments()
    {
        return $this->hasMany(PointOfSaleUpload::class)->where('type', 'fiscal_document');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeValidated($query)
    {
        return $query->where('status', 'validated');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Recherche libre (nom, numéro Flooz, shortcode, n° propriétaire, dealer).
     *
     * L'interface affiche les numéros avec des espaces ("228 96 77 75 58", "131 1244") alors que la base
     * les stocke sans : quand la saisie ne contient que des chiffres et des séparateurs, on compare donc
     * les chiffres seuls aux colonnes numériques.
     */
    public function scopeSearch($query, ?string $term)
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        $like = fn (string $value) => '%' . addcslashes($value, '\%_') . '%';
        $digits = preg_replace('/\D+/', '', $term);
        $looksLikeNumber = $digits !== '' && preg_match('/^[\d\s.\-+()]+$/', $term) === 1;

        return $query->where(function ($q) use ($term, $digits, $looksLikeNumber, $like) {
            $q->where('nom_point', 'like', $like($term));

            foreach (['numero_flooz', 'shortcode', 'numero_proprietaire'] as $column) {
                $q->orWhere($column, 'like', $like($looksLikeNumber ? $digits : $term));
            }

            // Dealer : sous-requête sur une table de quelques dizaines de lignes
            $q->orWhereIn('organization_id', Organization::query()->select('id')->where('name', 'like', $like($term)));
        });
    }

    /**
     * Restreint aux PDV visibles par l'utilisateur selon son rôle.
     */
    public function scopeVisibleTo($query, User $user)
    {
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isDealerOwner()) {
            return $query->where('organization_id', $user->organization_id);
        }

        if ($user->isCommercial()) {
            return $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhereHas('tasks', function ($taskQuery) use ($user) {
                      $taskQuery->where('assigned_to', $user->id);
                  });
            });
        }

        if ($user->isDealerAgent()) {
            return $query->where('created_by', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function tags()
    {
        return $this->hasMany(PointOfSaleTag::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class)->orderByDesc('is_pinned')->orderByDesc('created_at');
    }

    /**
     * Ajouter un tag au PDV
     */
    public function addTag($tag)
    {
        return $this->tags()->firstOrCreate(['tag' => $tag]);
    }

    /**
     * Retirer un tag du PDV
     */
    public function removeTag($tag)
    {
        return $this->tags()->where('tag', $tag)->delete();
    }

    /**
     * Retirer tous les tags du PDV
     */
    public function removeAllTags()
    {
        return $this->tags()->delete();
    }

    private const MISSING_PLACEHOLDERS = ['N/A', 'NA', 'NON RENSEIGNE', 'NON RENSEIGNÉ', 'NON RENSEIGNEE', 'NON RENSEIGNÉE'];

    public const REQUIRED_FIELDS = [
        'nom_point' => 'Nom du point de vente',
        'numero_flooz' => 'Numéro Flooz',
        'shortcode' => 'Shortcode',
        'profil' => 'Profil',
        'region' => 'Région',
        'prefecture' => 'Préfecture',
        'commune' => 'Commune',
        'ville' => 'Ville',
        'quartier' => 'Quartier',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude',
        'numero_proprietaire' => 'Téléphone propriétaire',
        'support_visibilite' => 'Support de visibilité',
        'numero_cagnt' => 'Numéro CAGNT',
    ];

    /**
     * Équivalent SQL de getMissingRequiredFieldsAttribute() : PDV ayant au moins un champ requis manquant.
     */
    public function scopeIncomplete($query)
    {
        return $query->where(function ($q) {
            foreach (array_keys(self::REQUIRED_FIELDS) as $field) {
                if (in_array($field, ['latitude', 'longitude'], true)) {
                    $q->orWhereNull($field)->orWhere($field, 0);
                    continue;
                }

                $q->orWhereNull($field)
                  ->orWhere($field, '')
                  ->orWhere($field, '0')
                  ->orWhereIn(\Illuminate\Support\Facades\DB::raw("UPPER(TRIM(`{$field}`))"), self::MISSING_PLACEHOLDERS);
            }

            $q->orWhere('numero_flooz', 'like', '990%')
              ->orWhere('numero_cagnt', 'like', '000%')
              ->orWhere('numero_proprietaire', 'like', '000%');
        });
    }

    /**
     * Champs requis considérés manquants
     */
    public function getMissingRequiredFieldsAttribute(): array
    {
        $placeholders = self::MISSING_PLACEHOLDERS;

        $required = [
            'nom_point' => 'Nom du point de vente',
            'numero_flooz' => 'Numéro Flooz',
            'shortcode' => 'Shortcode',
            'profil' => 'Profil',
            'region' => 'Région',
            'prefecture' => 'Préfecture',
            'commune' => 'Commune',
            'ville' => 'Ville',
            'quartier' => 'Quartier',
            'latitude' => 'Latitude',
            'longitude' => 'Longitude',
            'numero_proprietaire' => 'Téléphone propriétaire',
            'support_visibilite' => 'Support de visibilité',
            'numero_cagnt' => 'Numéro CAGNT',
        ];

        $missing = [];

        foreach ($required as $field => $label) {
            $value = $this->{$field};

            // Placeholders or empty
            $isPlaceholder = is_string($value) && in_array(strtoupper(trim($value)), array_map('strtoupper', $placeholders), true);

            // Placeholder numeric patterns used during import
            if ($field === 'numero_flooz' && is_string($value) && str_starts_with($value, '990')) {
                $isPlaceholder = true;
            }

            if ($field === 'numero_cagnt' && is_string($value) && ($value === '00000000000' || str_starts_with($value, '000'))) {
                $isPlaceholder = true;
            }

            if ($field === 'numero_proprietaire' && is_string($value) && ($value === '00000000000' || str_starts_with($value, '000'))) {
                $isPlaceholder = true;
            }

            $isEmpty = ($value === null || $value === '' || $value === 0 || $value === '0');

            // Coordonnées castées en "0.00000000" : considérées absentes (comme scopeIncomplete)
            if (in_array($field, ['latitude', 'longitude'], true) && is_numeric($value) && (float) $value == 0.0) {
                $isEmpty = true;
            }

            if ($isPlaceholder || $isEmpty) {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    /**
     * Vérifier si le PDV a un tag
     */
    public function hasTag($tag)
    {
        return $this->tags()->where('tag', $tag)->exists();
    }
}
