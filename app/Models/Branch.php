<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    //



    protected $fillable = [
        'entity_id',
        'name',
        'address',
        'contact_info',
        'external_id'
    ];

    //relationships
    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }
    public function stores()
    {
        return $this->hasMany(Store::class);
    }
    public function entityUsers()
    {
        return $this->hasMany(EntityUser::class);
    }
    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }
}
