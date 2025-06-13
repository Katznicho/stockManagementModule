<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    //
    // $table->integer('external_store_id')->nullable();
    // $table->integer('external_store_name')->nullable();



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
        'opening_stock',
        'external_store_id',
        'external_store_name',
        
    ];

    //relationships
    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }

    //item has many stock
    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }
}
