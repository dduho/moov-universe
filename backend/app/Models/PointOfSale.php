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
        'validated_by',
        'status',
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
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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
     * Vérifier si le PDV a un tag
     */
    public function hasTag($tag)
    {
        return $this->tags()->where('tag', $tag)->exists();
    }
}
