<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductStockLevel extends Model
{
    //
    protected $fillable = [
        'item_id',
        'opening_stock',
        'deliveries_to_date',
        'sales_to_date',
        'returns',
        'external_id',
        'external_item_id'
    ];

    // Accessor for Current Stock
    public function getCurrentStockAttribute(): int
    {
        return $this->opening_stock + $this->deliveries_to_date - $this->sales_to_date + $this->returns;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
