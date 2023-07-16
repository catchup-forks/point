<?php

namespace App\Model\Sales\SalesContract;

use App\Model\Master\Item;
use App\Model\TransactionModel;

class SalesContractItem extends TransactionModel
{
    protected $connection = 'tenant';

    public $timestamps = false;

    protected $fillable = [
        'item_id',
        'price',
        'quantity',
        'discount_percent',
        'discount_value',
        'unit',
        'converter',
        'notes',
        'allocation_id',
    ];

    protected $casts = [
        'price' => 'double',
        'quantity' => 'double',
        'converter' => 'double',
        'discount_percent' => 'double',
        'discount_value' => 'double',
    ];

    public function salesContract()
    {
        return $this->belongsTo(SalesContract::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
