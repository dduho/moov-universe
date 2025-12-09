<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'point_of_sale_id',
        'user_id',
        'content',
        'is_pinned',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
    ];

    /**
     * Relation vers le point de vente
     */
    public function pointOfSale()
    {
        return $this->belongsTo(PointOfSale::class);
    }

    /**
     * Relation vers l'utilisateur qui a créé la note
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
