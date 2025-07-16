<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Item;
use App\Models\Entity;

class OrderController extends Controller
{
    public function index()
    {
        return Order::all();
    }
    public function store(Request $request)
    {
        $request->validate([
            'entity_id' => 'required|exists:entities,id',
            'branch_id' => 'required|exists:branches,id',
            'item_id' => 'required|exists:items,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'order_qty_suom' => 'required|numeric',
            'order_qty_ouom' => 'required|numeric',
            'order_amount_ugx' => 'required|numeric',
            'order_date' => 'required|date',
            'status' => 'required|string'
        ]);
        return Order::create($request->all());
    }
    public function show($id)
    {
        return Order::findOrFail($id);
    }
    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $order->update($request->all());
        return $order;
    }
    public function destroy($id)
    {
        Order::destroy($id);
        return response()->noContent();
    }


 public function storeBulkOrder(Request $request)
{
    try {
        // Validate the incoming request
        $validatedData = $request->validate([
            'branch_id' => 'required|integer',
            'items' => 'required|array|min:1',
            'items.*' => 'integer',
            'order_by_days' => 'required|integer|min:1', // Required for forecasting
            'order_by_budget' => 'required|numeric|min:1', // Required as a constraint
            'bulk_order_date' => 'required|date|after_or_equal:' . now()->toDateString(),
            'external_id' => 'required',
        ], [
            'bulk_order_date.after_or_equal' => 'The order date must be today or a future date (after ' . now()->toDateString() . ').',
            'order_by_days.required' => 'Forecast demand days are required for predictions.',
            'order_by_budget.required' => 'Budget is required as a constraint for predictions.',
        ]);

        // Placeholder data (to be replaced with actual inventory data)
        $dailyMovingAverages = [
            15 => 2,   // Bi-Monthly Daily Moving Average (SUOM) - V!
            30 => 1.5, // Monthly Daily Moving Average (SUOM) - AA!
            90 => 1,   // Quarterly Daily Moving Average (SUOM)
            180 => 0.8, // Biannual Daily Moving Average (SUOM)
            360 => 0.5, // Annual Daily Moving Average (SUOM)
        ];
        $safetyStockDays = 5; // AB! - Safety Stock Days
        $bufferStockDays = 10; // AD! - Buffer Stock Days
        $purchasePrice = 100; // F! - Purchase Price per SUOM
        $duomToSuom = 1; // J! - DUOM to SUOM conversion factor
        $ba7 = 15; // Placeholder for BA7 (assumed base period constant)

        // Fetch item details from the Item model
        $items = Item::whereIn('id', $validatedData['items'])->get()->map(function ($item) use ($dailyMovingAverages, $purchasePrice, $duomToSuom) {
            return (object) [
                'id' => $item->id,
                'name' => $item->name,
                'current_stock_suom' => $item->opening_stock ?? 50, // Use opening_stock or fallback to 50
                'physical_stock_suom' => $item->opening_stock ?? 50, // Placeholder
                'purchase_price' => $item->purchase_price ?? $purchasePrice, // Use model data or fallback
                'duom_to_suom' => $item->suom_per_duom ?? $duomToSuom, // Use model data or fallback
                'biMonthlyAverage' => $dailyMovingAverages[15], // V!
                'monthlyAverage' => $dailyMovingAverages[30], // AA!
            ];
        })->all();

        // Calculate predictions for each item (AI-AM) using both days and budget
        $totalOrderQty = 0;
        $totalOrderAmount = 0;
        $payload = ['predictions' => []];

        foreach ($items as $item) {
            // Calculate current stock level days (simplified)
            $currentStockDays = $item->current_stock_suom / ($dailyMovingAverages[30] ?: 1); // N! / (V! or AA!)

            // Use order_by_days for the order period
            $orderPeriodDays = $validatedData['order_by_days'];
            $matchedPeriod = min(array_keys($dailyMovingAverages), function ($a, $b) use ($orderPeriodDays) {
                return abs($a - $orderPeriodDays) <=> abs($b - $orderPeriodDays);
            })[0]; // Closest period

            // Select appropriate daily moving average
            $movingAverage = $dailyMovingAverages[$matchedPeriod] ?? $dailyMovingAverages[30] ?? 1;

            // AI: Gap to Average Days left to Order
            $summationAm = $currentStockDays; // Mock summation of AM! (simplified)
            $countAm = 1; // Mock count of AM! (simplified)
            $gapToAverageDays = $currentStockDays - ($summationAm / $countAm);

            // AJ: Order Days
            $summationAh = $dailyMovingAverages[15] ?: 1; // Mock summation of AH! (bi-monthly average)
            $orderDays = ($ba7 * $movingAverage / $summationAh) - $gapToAverageDays;

            // AK: Order Qty (based on days)
            $orderQtySuom = max(0, $orderDays * ($item->biMonthlyAverage ?: $item->monthlyAverage ?: 1));
            if ($orderQtySuom <= 0) continue; // Filter out non-positive quantities

            // AL: Order Amount (initial calculation)
            $orderAmount = $orderQtySuom * ($item->purchase_price / $item->duom_to_suom);

            // AM: Days left to Order
            $daysLeftToOrder = $currentStockDays - ($safetyStockDays + $bufferStockDays);

            // Adjust based on budget for hybrid prediction
            $maxQtyByBudget = floor($validatedData['order_by_budget'] / ($item->purchase_price / $item->duom_to_suom));
            $adjustedOrderQtySuom = min($orderQtySuom, $maxQtyByBudget); // Balance demand (days) with budget constraint
            $adjustedOrderAmount = $adjustedOrderQtySuom * ($item->purchase_price / $item->duom_to_suom);

            // Add to payload with full prediction including item name
            $payload['predictions'][] = [
                'item_id' => $item->id,
                'item_name' => $item->name, // Fetch from Item model
                'order_period_days' => $matchedPeriod,
                'gap_to_average_days' => $gapToAverageDays, // AI
                'order_days' => $orderDays, // AJ
                'order_qty_suom' => $adjustedOrderQtySuom, // AK (adjusted)
                'order_amount_ugx' => $adjustedOrderAmount, // AL (adjusted)
                'days_left_to_order' => $daysLeftToOrder, // AM
            ];

            $totalOrderQty += $adjustedOrderQtySuom;
            $totalOrderAmount += $adjustedOrderAmount;
        }

        if (empty($payload['predictions'])) {
            return response()->json([
                'success' => false,
                'message' => 'No valid order quantities calculated.',
            ], 400);
        }

        // Calculate Summation AL (total order amount as per spreadsheet)
        $summationAL = $totalOrderAmount;

        return response()->json([
            'success' => true,
            'message' => 'Order predictions calculated successfully using both days and budget.',
            'summation_al' => $summationAL,
            'data' => $payload,
        ], 200);
    } catch (\Exception $e) {
        Log::error('Error in storeBulkOrder: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'An error occurred while calculating order predictions. Please try again.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}
