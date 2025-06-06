<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    //



    protected $fillable = [
        'entity_id',
        'name',
        'item_code',
        'suom',
        'duom',
        'ouom',
        'suom_per_duom',
        'suom_per_ouom',
        'purchase_price',
        'external_id',
        'external_item_id',
        'quantity',
        'date_of_delivery',
        'batch_no',
        'sale_units_per_delivery_unit',
        'daily_consumption',
        'safety_stock_days',
        'buffer_stock',
        'opening_stock'
    ];

    //relationships
    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }
}
