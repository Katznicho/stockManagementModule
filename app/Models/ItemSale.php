<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'external_item_id',
        'external_store_id', // Assuming this is the entity_id
        'entity_id',
        'external_id',
        'quantity_suom',
        'source',
        'reference',
        'remarks',
        'sold_at',
        'price', // Assuming price is a float
    ];

    protected $casts = [
        'sold_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
