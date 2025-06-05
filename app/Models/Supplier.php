<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    //
    protected $fillable = [
        'entity_id',
        'name',
        'contact_info',
        'lead_time_days',
        'external_id'
    ];
    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }
    // Add any other relationships or methods as needed
    // ...
}
