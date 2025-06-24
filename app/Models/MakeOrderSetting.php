<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MakeOrderSetting extends Model
{


    protected $fillable = [
        'parameter',
        'entity_id',
        'value',
        'amount_days_until_next_order',
    ];
 
}
