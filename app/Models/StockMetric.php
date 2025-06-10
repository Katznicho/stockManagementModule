<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMetric extends Model
{


    protected $fillable = [
        'entity_id',
        'branch_id',
        'item_id',
        'store_id',
        'bi_monthly_avg_suom',
        'monthly_avg_suom',
        'quarterly_avg_suom',
        'biannual_avg_suom',
        'annual_avg_suom',  
        'fixed_avg_suom',
        'safety_stock_days',
        'safety_stock_suom',
        'buffer_stock_days',
        'buffer_stock_suom',
        'external_id',
        'entity_id',
        'external_id',
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
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
