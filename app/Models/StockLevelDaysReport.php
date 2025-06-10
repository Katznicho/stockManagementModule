<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockLevelDaysReport extends Model
{
    //
    protected $fillable = [
        'current_stock_level',
        'daily_sales',
        'average_sales',
        'stock_level_days',
        'external_item_id',
        'item_id',
        'entity_id',
        'external_id',
    ];

    protected static function booted()
    {
        static::saving(function ($report) {
            $sales = $report->daily_sales > 0 ? $report->daily_sales : $report->average_sales;
            $report->stock_level_days = $sales > 0 ? $report->current_stock_level / $sales : 0;
        });
    }
}
