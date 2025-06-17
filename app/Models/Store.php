<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{


    protected $fillable = [
        'entity_id',
        'branch_id',
        'external_id',
        'name',
        'level',
        'parent_store_id',
        'external_store_id',
        'external_store_name',
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

    public function parentStore()
    {
        return $this->belongsTo(Store::class, 'parent_store_id');
    }
    public function childStores()
    {
        return $this->hasMany(Store::class, 'parent_store_id');
    }
}
