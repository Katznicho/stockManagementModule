<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shrinkage extends Model
{
    // $table->id();


    protected $fillable = [
        'entity_id',
        'branch_id',
        'item_id',
        'store_id',
        'system_qty_suom',
        'physical_qty_suom',
        'shrinkage_percentage',
        'shrinkage_amount_ugx',
        'stock_take_date',  
        'external_id',
        'branch_name'
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
