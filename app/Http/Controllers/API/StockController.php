<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemSetting;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index()
    {
        return Stock::all();
    }


    public function store(Request $request)
{
    try {
        $validated = $request->validate([
            'product_id' => 'required|integer',
            'branch_id' => 'required|integer',
            'store_id' => 'required|integer',
            'quantity' => 'required|numeric|min:1',
            'batch_number' => 'required|string',
            'duom' => 'required|string',
            'purchase_price' => 'required|numeric|min:0',
            'delivery_date' => 'required|date',
            'suom' => 'required|string',
            'sale_units_per_delivery' => 'required|numeric|min:1',
            'qty_sale_units' => 'required|numeric|min:0',
            'external_id' => 'required|integer', // Treated as entity_id
        ]);

        // Check for ItemSetting
        $itemSetting = ItemSetting::where('entity_id', $validated['external_id'])
            ->where('external_item_id', $validated['product_id'])
            ->first();

        if (!$itemSetting) {
            return response()->json([
                'message' => 'Setup failed',
                'success' => false,
                'data' => 'No item setting found for the given entity and product'
            ], 400);
        }

        // Begin database transaction
        DB::beginTransaction();

        // Find or create Item
        $item = Item::where([
            'entity_id' => $validated['external_id'],
            'external_item_id' => $validated['product_id'],
        ])->first();

        if ($item) {
            // Increment existing item quantity by delivery units
            $item->increment('quantity', $validated['quantity']);
        } else {
            // Create new item
            $item = Item::create([
                'entity_id' => $validated['external_id'],
                'external_item_id' => $validated['product_id'],
                'name' => $itemSetting->name ?? 'Product ' . $validated['product_id'],
                'item_code' => 'ITEM-' . $validated['product_id'] . '-' . time(),
                'external_id' => $validated['external_id'],
                'quantity' => $validated['quantity'], // Initial quantity in delivery units
                // 'suom' => $validated['suom'],
                // 'duom' => $validated['duom'],
                // 'ouom' => $validated['duom'], // Assuming ouom is same as duom
                // 'suom_per_duom' => $validated['sale_units_per_delivery'],
                // 'suom_per_ouom' => $validated['sale_units_per_delivery'],
                // 'purchase_price' => $validated['purchase_price'],
                // 'opening_stock' => $validated['qty_sale_units'], // Opening stock in sale units
                // 'daily_consumption' => 0, // Uncomment if needed
                // 'safety_stock_days' => 0, // Uncomment if needed
                // 'buffer_stock' => 0, // Uncomment if needed
            ]);
        }

        // Create Stock entry
        $stock = Stock::create([
            'entity_id' => $validated['external_id'],
            'branch_id' => $validated['branch_id'],
            'item_id' => $item->id,
            'store_id' => $validated['store_id'],
            'batch_no' => $validated['batch_number'],
            'current_stock_suom' => $validated['qty_sale_units'],
            'opening_stock_suom' => $validated['qty_sale_units'],
            'closing_stock_suom' => $validated['qty_sale_units'], // Initial closing stock
            'date_of_delivery' => $validated['delivery_date'],
            'stock_aging_days' => 0, // Initial value; calculate later if needed
            'external_id' => $validated['external_id'],
            'suom' => $validated['suom'],
            'duom' => $validated['duom'],
            'ouom' => $validated['duom'], // Assuming ouom is same as duom
            'suom_per_duom' => $validated['sale_units_per_delivery'],
            'suom_per_ouom' => $validated['sale_units_per_delivery'],
            'purchase_price' => $validated['purchase_price'],
        ]);

        // Commit transaction
        DB::commit();

        return response()->json([
            'message' => 'Stock created successfully',
            'success' => true,
            'data' => [
                'stock' => $stock,
                'item' => $item,
            ]
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Validation failed',
            'success' => false,
            'errors' => $e->errors()
        ], 422);
    } catch (\Throwable $th) {
        // Rollback transaction on error
        DB::rollBack();
        return response()->json([
            'message' => 'Failed to create stock',
            'success' => false,
            'error' => $th->getMessage()
        ], 500);
    }
}

    public function show($id)
    {
        return Stock::findOrFail($id);
    }
    public function update(Request $request, $id)
    {
        $stock = Stock::findOrFail($id);
        $stock->update($request->all());
        return $stock;
    }
    public function destroy($id)
    {
        Stock::destroy($id);
        return response()->noContent();
    }

    public  function getStockByExternalId($externalId) {
        try {
            $store = Item::where('external_id', $externalId)
            ->get();
            return response()->json(['data' => $store,'success' => true]);
        }
        catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching stores.','success' => false]);
        }
    }
}
