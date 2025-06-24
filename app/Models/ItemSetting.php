<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemSetting extends Model
{
    //


    protected $fillable = [
        'item_id',
        'external_item_id',
        'daily_consumption',
        'safety_stock_days',
        'buffer_stock',
        'buffer_stock_days',
        'safety_stock',
        'opening_stock',
        'name',
        'entity_id',
        'external_id',
        'external_store_id',
        'external_store_name'
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
