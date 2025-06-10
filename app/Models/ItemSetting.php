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
        'opening_stock',
        'name',
        'entity_id',
        'external_id',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
