<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Entity;
use App\Models\Item;
use App\Models\ItemSetting;
use App\Models\ProductStockLevel;
use App\Models\Stock;
use App\Models\StockLevelDaysReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ItemSale;

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
        $itemSetting = ItemSetting::where('external_id', $validated['external_id'])
            ->where('external_item_id', $validated['product_id'])
            ->first();

        if (!$itemSetting) {
            return response()->json([
                'message' => 'Setup failed',
                'success' => false,
                'data' => 'No item setting found for the given entity and product'
            ], 400);
        }

        $entity =  Entity::where('external_id', $validated['external_id'])->first();
        if (!$entity) {
            return response()->json([
               'message' => 'Setup failed',
               'success' => false,
                'data' => 'No entity found for the given external_id'
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
 
            ]);


            ProductStockLevel::create([
                'item_id' => $item->id, // ✅ Correct: use DB ID
                'external_item_id' => $item->external_item_id,
                'opening_stock' => $itemSetting->opening_stock?? 0,
                'deliveries_to_date' => 0,
                'sales_to_date' => 0,
                'returns' => 0,
                'external_id' =>$validated['external_id'],
                'entity_id' => $entity->id,
            ]);

            StockLevelDaysReport::create([
                'item_id' => $item->id, // ✅ Correct: use DB ID
                'external_item_id' => $item->external_item_id,
                'current_stock_level' => $itemSetting->opening_stock,
                'daily_sales' => 0,
                'average_sales' => 0,
                'stock_level_days' => 0,
                'entity_id' => $entity->id,
                'external_id' => $validated['external_id'],
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
            // 'ouom' => $validated['duom'], // Assuming ouom is same as duom
            'suom_per_duom' => $validated['sale_units_per_delivery'],
            'suom_per_ouom' => $validated['sale_units_per_delivery'],
            'purchase_price' => $validated['purchase_price'],
            'no_of_sale_units_per_duom'=> $validated['sale_units_per_delivery'],
            'qty_sale_units_purchased'=> $validated['qty_sale_units'],
            'qty'=>$validated['quantity'], // Total quantity purchased in DUOM or SUOM
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



public function reduceStock(Request $request)
{
    try {
        $validated = $request->validate([
            'product_id' => 'required|integer',
            'external_id' => 'required|integer',
            'quantity' => 'required|numeric|min:1', // quantity in SUOM
        ]);

        DB::beginTransaction();

        $item = Item::where([
            'external_item_id' => $validated['product_id'],
            'entity_id' => $validated['external_id'],
        ])->first();

        if (!$item) {
            return response()->json([
                'message' => 'Item not found',
                'success' => false,
            ], 404);
        }

        // Get SUOM per DUOM conversion ratio
        $suomPerDuom = $item->suom_per_duom ?? Stock::where('item_id', $item->id)->value('suom_per_duom');
        if (!$suomPerDuom || $suomPerDuom <= 0) {
            return response()->json([
                'message' => 'Conversion ratio (suom_per_duom) not found',
                'success' => false,
            ], 400);
        }

        $suomToReduce = $validated['quantity'];
        $duomToReduce = $suomToReduce / $suomPerDuom;

        // Check if enough DUOM in item
        if ($item->quantity < $duomToReduce) {
            return response()->json([
                'message' => 'Not enough stock in DUOM',
                'success' => false,
                'available_duom' => $item->quantity,
                'requested_duom' => $duomToReduce
            ], 400);
        }

        // Reduce DUOM quantity from item
        $item->decrement('quantity', $duomToReduce);

        // Update Product Stock Level (SUOM)
        ProductStockLevel::where('item_id', $item->id)->increment('sales_to_date', $suomToReduce);

        // Update Stock Level Report (SUOM)
        StockLevelDaysReport::where('item_id', $item->id)->decrement('current_stock_level', $suomToReduce);

        // Reduce from Stock records (SUOM)
        $remainingSuom = $suomToReduce;
        $stocks = Stock::where('item_id', $item->id)
            ->where('closing_stock_suom', '>', 0)
            ->orderBy('date_of_delivery')
            ->get();

        foreach ($stocks as $stock) {
            if ($remainingSuom <= 0) break;

            $deduct = min($remainingSuom, $stock->closing_stock_suom);
            $stock->decrement('closing_stock_suom', $deduct);
            $remainingSuom -= $deduct;
        }

        // Log the sale
        ItemSale::create([
            'item_id' => $item->id,
            'external_item_id' => $item->external_item_id,
            'entity_id' => $item->entity_id,
            'external_id' => $validated['external_id'],
            'quantity_suom' => $suomToReduce,
            'source' => 'api',
            'reference' => null,
            'remarks' => null,
            'sold_at' => now(),
        ]);

        DB::commit();

        return response()->json([
            'message' => 'Stock reduced successfully',
            'success' => true,
            'data' => [
                'item_id' => $item->id,
                'remaining_duom' => $item->quantity,
                'reduced_suom' => $suomToReduce,
            ]
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Validation failed',
            'success' => false,
            'errors' => $e->errors()
        ], 422);
    } catch (\Throwable $th) {
        DB::rollBack();
        return response()->json([
            'message' => 'Failed to reduce stock',
            'success' => false,
            'error' => $th->getMessage()
        ], 500);
    }
}

//reduce bulky stock
public function reduceStockBulk(Request $request)
{
    try {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.external_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|min:1', // quantity in SUOM
        ]);

        DB::beginTransaction();

        foreach ($validated['items'] as $entry) {
            $item = Item::where([
                'external_item_id' => $entry['product_id'],
                'entity_id' => $entry['external_id'],
            ])->first();

            if (!$item) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Item not found for product ID: ' . $entry['product_id'],
                    'success' => false,
                ], 404);
            }

            $suomPerDuom = $item->suom_per_duom ?? Stock::where('item_id', $item->id)->value('suom_per_duom');
            if (!$suomPerDuom || $suomPerDuom <= 0) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Conversion ratio not found for product ID: ' . $entry['product_id'],
                    'success' => false,
                ], 400);
            }

            $suomQty = $entry['quantity'];
            $duomQty = $suomQty / $suomPerDuom;

            if ($item->quantity < $duomQty) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Not enough stock for product ID: ' . $entry['product_id'],
                    'success' => false,
                    'available_duom' => $item->quantity,
                    'requested_duom' => $duomQty
                ], 400);
            }

            $item->decrement('quantity', $duomQty);

            ProductStockLevel::where('item_id', $item->id)->increment('sales_to_date', $suomQty);
            StockLevelDaysReport::where('item_id', $item->id)->decrement('current_stock_level', $suomQty);

            $remainingSuom = $suomQty;
            $stocks = Stock::where('item_id', $item->id)
                ->where('closing_stock_suom', '>', 0)
                ->orderBy('date_of_delivery')
                ->get();

            foreach ($stocks as $stock) {
                if ($remainingSuom <= 0) break;

                $deduct = min($remainingSuom, $stock->closing_stock_suom);
                $stock->decrement('closing_stock_suom', $deduct);
                $remainingSuom -= $deduct;
            }

            // Log the sale
            ItemSale::create([
                'item_id' => $item->id,
                'external_item_id' => $item->external_item_id,
                'entity_id' => $item->entity_id,
                'external_id' => $entry['external_id'],
                'quantity_suom' => $suomQty,
                'source' => 'api',
                'reference' => null,
                'remarks' => null,
                'sold_at' => now(),
            ]);
        }

        DB::commit();

        return response()->json([
            'message' => 'Bulk stock reduction successful',
            'success' => true
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Validation failed',
            'success' => false,
            'errors' => $e->errors()
        ], 422);
    } catch (\Throwable $th) {
        DB::rollBack();
        return response()->json([
            'message' => 'Bulk reduction failed',
            'success' => false,
            'error' => $th->getMessage()
        ], 500);
    }
}

//reduce stock in bulk



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
             ->with(['stocks'])
            ->get();
            return response()->json(['data' => $store,'success' => true]);
        }
        catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching stores.','success' => false]);
        }
    }
}
