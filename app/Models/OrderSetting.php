<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderSetting extends Model
{
    //
    protected $fillable = [
        'notification_to_order_days',
        'entity_id',
        'external_id',
        'anticipated_peak_period_percentage',
        'expected_increase_during_peak',
        'shrinkage_total_amount',
    ];
}
