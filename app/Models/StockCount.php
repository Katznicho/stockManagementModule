<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockCount extends Model
{
    //
 
    protected $fillable = [
        'entity_id',
        'item_id',
        'external_item_id',   
        'external_id', // external_id of the entity
        'date',
        'physical_stock_suom',
        'damaged_stock_suom',
    ];  
}
