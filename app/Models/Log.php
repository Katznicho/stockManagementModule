<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    //
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'changed_data',
        'description',
        'ip_address',
        'user_agent',
        'external_id'
    ];


    protected $casts = [
        'changed_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(EntityUser::class, 'user_id');
    }
}
