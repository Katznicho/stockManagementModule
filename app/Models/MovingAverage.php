<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovingAverage extends Model
{


    protected $fillable = [
        'entity_id',
        'item_id',
        'external_item_id',
        'external_id',
        'bi_monthly_suom',
        'monthly_suom',
        'quarterly_suom',
        'biannual_suom',
        'annual_suom',
    ];
}
