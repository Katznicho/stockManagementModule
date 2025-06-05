<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntityUser extends Model
{
    //


    protected $fillable = [
        'entity_id',
        'branch_id',
        'name',
        'email',
        'password',
        'store_id',
        'role', 
        'external_id'
    ];

    //relationship
    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
