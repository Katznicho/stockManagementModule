<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{


    protected $fillable = [
        'entity_id',
        'branch_id',
        'item_id',
        'supplier_id',
        'order_qty_suom',
        'order_qty_ouom',
        'order_amount_ugx',
        'order_date',
        'status',
        'external_id'
    ];

    //relationships
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
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
