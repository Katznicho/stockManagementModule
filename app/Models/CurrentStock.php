<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrentStock extends Model
{
    //


    protected $fillable = [
        'entity_id',
        'item_id',
        'external_item_id',     
        'external_id',
        'physical_stock',
        'opening_stock',
        'purchases',
        'sales',
        'transfers',
        'date',
    ];
}
