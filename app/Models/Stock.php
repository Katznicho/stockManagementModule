<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    //
    // use LogsActivity;


    protected $fillable = [
        'entity_id',
        'branch_id',
        'item_id',
        'store_id',
        'batch_no',
        'current_stock_suom',
        'opening_stock_suom',
        'closing_stock_suom',
        'date_of_delivery',
        'stock_aging_days',
        'external_id',
        'purchase_price',
        'qty_sale_units_purchased',
        'no_of_sale_units_per_duom',
        'suom',
        'duom',
        'ouom',
        'suom_per_duom',
        'suom_per_ouom',
        'qty_sale_units',
        'external_id',
        'qty',
        'external_store_id'
        
    ];


    //relationships
    public function entity() { return $this->belongsTo(Entity::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function store() { return $this->belongsTo(Store::class); }
}
