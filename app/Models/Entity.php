<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entity extends Model
{
    //


    protected $fillable = [
        'name',
        'logo',
        'address',
        'contact_info',
        'external_id'
    ];
}
