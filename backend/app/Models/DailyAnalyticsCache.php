<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyAnalyticsCache extends Model
{
    protected $table = 'daily_analytics_cache';

    protected $fillable = [
        'date',
        'total_ca',
        'total_transactions',
        'total_volume',
        'pdv_actifs',
        'total_depots',
        'total_depots_amount',
        'total_retraits',
        'total_retraits_amount',
        'total_transfers',
        'total_transfers_amount',
        'total_commission_pdv',
        'total_commission_dealers',
    ];

    protected $casts = [
        'date' => 'date',
        'total_ca' => 'decimal:2',
        'total_volume' => 'decimal:2',
        'total_depots_amount' => 'decimal:2',
        'total_retraits_amount' => 'decimal:2',
        'total_transfers_amount' => 'decimal:2',
        'total_commission_pdv' => 'decimal:2',
        'total_commission_dealers' => 'decimal:2',
    ];
}
