<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockReturn extends Model
{
    //


    protected $fillable = [
        'entity_id',
        'branch_id',
        'item_id',
        'store_id',
        'qty_suom',
        'reason',
        'return_date',  
        'external_id',
        'entity_id',
        'external_id',
    ];

    //relations
    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    public function item()
    {
        return $this->belongsTo(Item::class);
    }
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
