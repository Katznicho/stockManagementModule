<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemStock extends Model
{
    //
    protected $fillable = [
        'entity_id',
        'item_id',
        'external_item_id',
        'external_id',      
        'opening_stock',
        'purchases',
        'sales',
        'returns',
        'date',
    ];
}
