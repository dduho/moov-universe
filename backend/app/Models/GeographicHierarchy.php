<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeographicHierarchy extends Model
{
    use HasFactory;

    protected $fillable = [
        'region',
        'prefecture',
        'commune',
        'canton',
    ];

    public static function getRegions()
    {
        return self::select('region')->distinct()->pluck('region');
    }

    public static function getPrefecturesByRegion($region)
    {
        return self::where('region', $region)->select('prefecture')->distinct()->pluck('prefecture');
    }

    public static function getCommunesByPrefecture($prefecture)
    {
        return self::where('prefecture', $prefecture)->select('commune')->distinct()->pluck('commune');
    }

    public static function getCantonsByCommune($commune)
    {
        return self::where('commune', $commune)->select('canton')->distinct()->whereNotNull('canton')->pluck('canton');
    }
}
