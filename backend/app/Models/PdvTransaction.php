<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PdvTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'pdv_numero',
        'transaction_date',
        // Dépôts
        'count_depot',
        'sum_depot',
        'pdv_depot_commission',
        'dealer_depot_commission',
        'pdv_depot_retenue',
        'dealer_depot_retenue',
        'depot_keycost',
        'depot_customer_tva',
        // Retraits
        'count_retrait',
        'sum_retrait',
        'pdv_retrait_commission',
        'dealer_retrait_commission',
        'pdv_retrait_retenue',
        'dealer_retrait_retenue',
        'retrait_keycost',
        'retrait_customer_tva',
        // Transferts envoyés
        'count_give_send',
        'sum_give_send',
        'count_give_send_in_network',
        'sum_give_send_in_network',
        'count_give_send_out_network',
        'sum_give_send_out_network',
        // Transferts reçus
        'count_give_receive',
        'sum_give_receive',
        'count_give_receive_in_network',
        'sum_give_receive_in_network',
        'count_give_receive_out_network',
        'sum_give_receive_out_network',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        // Dépôts
        'count_depot' => 'integer',
        'sum_depot' => 'decimal:2',
        'pdv_depot_commission' => 'decimal:2',
        'dealer_depot_commission' => 'decimal:2',
        'pdv_depot_retenue' => 'decimal:2',
        'dealer_depot_retenue' => 'decimal:2',
        'depot_keycost' => 'decimal:2',
        'depot_customer_tva' => 'decimal:2',
        // Retraits
        'count_retrait' => 'integer',
        'sum_retrait' => 'decimal:2',
        'pdv_retrait_commission' => 'decimal:2',
        'dealer_retrait_commission' => 'decimal:2',
        'pdv_retrait_retenue' => 'decimal:2',
        'dealer_retrait_retenue' => 'decimal:2',
        'retrait_keycost' => 'decimal:2',
        'retrait_customer_tva' => 'decimal:2',
        // Transferts
        'count_give_send' => 'integer',
        'sum_give_send' => 'decimal:2',
        'count_give_send_in_network' => 'integer',
        'sum_give_send_in_network' => 'decimal:2',
        'count_give_send_out_network' => 'integer',
        'sum_give_send_out_network' => 'decimal:2',
        'count_give_receive' => 'integer',
        'sum_give_receive' => 'decimal:2',
        'count_give_receive_in_network' => 'integer',
        'sum_give_receive_in_network' => 'decimal:2',
        'count_give_receive_out_network' => 'integer',
        'sum_give_receive_out_network' => 'decimal:2',
    ];

    /**
     * Relation avec le PDV
     */
    public function pdv()
    {
        return $this->belongsTo(Pdv::class, 'pdv_numero', 'numero_flooz');
    }
}
