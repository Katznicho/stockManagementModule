<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryValuationReport extends Model
{
    //

            
protected $fillable = [
        'current_stock_level',
        'price_per_litre',
        'conversion_rate',
        'inventory_valuation',
        'external_item_id',
        'item_id'
    ];

    protected static function booted()
    {
        static::saving(function ($report) {
            $report->inventory_valuation = $report->current_stock_level * $report->price_per_litre / $report->conversion_rate;
        });
    }
}
