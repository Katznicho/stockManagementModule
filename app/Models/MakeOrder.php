<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MakeOrder extends Model
{
    //


                protected $fillable = [
                    'forecast_demand_days',
                    'forecast_amount_ugx',
                    'forecast_days_till_next_order',
                    'order_budget',
                    'budget_amount_ugx',
                    'budget_days_till_next_order',
                    'external_id',
                ];
}
