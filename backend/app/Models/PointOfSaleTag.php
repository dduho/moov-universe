<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointOfSaleTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'point_of_sale_id',
        'tag'
    ];

    public function pointOfSale()
    {
        return $this->belongsTo(PointOfSale::class);
    }
}
