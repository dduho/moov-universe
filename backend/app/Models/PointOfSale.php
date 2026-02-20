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
    ];

    protected $appends = ['has_active_task', 'has_task_in_revision', 'geo_validation', 'missing_required_fields'];

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
        
        $geoService = new \App\Services\GeoValidationService();
        return $geoService->validateRegionCoordinates(
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
        return $this->tasks()->whereNotIn('status', ['validated'])->exists();
    }

    /**
     * Accessor pour vérifier si le PDV a une tâche en révision demandée
     * C'est le seul cas où un commercial peut modifier un PDV validé
     */
    public function getHasTaskInRevisionAttribute()
    {
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

    /**
     * Champs requis considérés manquants
     */
    public function getMissingRequiredFieldsAttribute(): array
    {
        $placeholders = ['N/A', 'NA', 'NON RENSEIGNE', 'NON RENSEIGNÉ', 'NON RENSEIGNEE', 'NON RENSEIGNÉE'];

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
