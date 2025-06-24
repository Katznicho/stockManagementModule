<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Entity;
use App\Models\Item;
use App\Models\ItemSetting;
use App\Models\ProductStockLevel;
use App\Models\Stock;
use App\Models\StockLevelDaysReport;
use App\Models\StockCount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ItemSale;
use App\Models\MovingAverage;
use App\Models\Order;
use App\Models\SystemStock;
use App\Models\CurrentStock;
use Carbon\Carbon;

class StockController extends Controller
{
    public function index()
    {
        return Stock::all();
    }




    public function stockCount(Request $request)
    {
        try {
            $validated = $request->validate([
                'external_id' => 'required|integer',
                'external_item_id' => 'required|integer',
                'branch_id' => 'required|integer',
                'physical_stock_suom' => 'required|numeric|min:0',
                'stock_count_date' => 'required|date',
                'damaged_stock_suom' => 'nullable|numeric|min:0', //

                // 'store_id' => 'required|integer',
            ]);

            $entity = Entity::where('external_id', $validated['external_id'])->first();
            if (!$entity) {
                return response()->json([
                    'message' => 'Setup failed',
                    'success' => false,
                    'data' => 'No entity found for the given external_id'
                ], 400);
            }
            // Check if the item exists
            $item = Item::where([
                'external_item_id' => $validated['external_item_id'],
                'entity_id' => $entity->id,
            ])->first();
            if (!$item) {
                return response()->json([
                    'message' => 'Item not found',
                    'success' => false,
                ], 404);
            }

             //create a new  stock count record
            $stockCount = StockCount::create([
                'entity_id' => $entity->id,
                'external_item_id' => $validated['external_item_id'],
                'branch_id' => $validated['branch_id'],
                'physical_stock_suom' => $validated['physical_stock_suom'],
                'damaged_stock_suom' => $validated['damaged_stock_suom'] ?? 0,
                'date' => $validated['stock_count_date'],
                'external_id' => $validated['external_id'],
            ]);
            if(!$stockCount) {
                return response()->json([
                    'message' => 'Failed to create stock count',
                    'success' => false,
                ], 500);
            }

            //update the current stock
            $currentStock = CurrentStock::where('item_id', $item->id)->first();
            if ($currentStock) {
                $currentStock->physical_stock += $validated['physical_stock_suom'];
            } 

            return response()->json([
                'message' => 'Stock count retrieved successfully',
                'success' => true,
                'data' => $stockCount
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed to retrieve stock count',
                'success' => false,
                'error' => $th->getMessage()
            ], 500);
        }
    }
    //stock count    
    public function store(Request $request)
    {
        try {
            $data = $request->all();

            // Default order_date to delivery_date if not provided
            if (!isset($data['order_date'])) {
                $data['order_date'] = $request->input('delivery_date');
            }

            $validated = $request->validate([
                'product_id' => 'required|integer',
                'branch_id' => 'required|integer',
                'store_id' => 'required|integer',
                'quantity' => 'required|numeric|min:1',
                'batch_number' => 'required|string',
                'duom' => 'required|string',
                'purchase_price' => 'required|numeric|min:0',
                'delivery_date' => 'required|date',
                'order_date' => 'nullable|date',
                'suom' => 'required|string',
                'sale_units_per_delivery' => 'required|numeric|min:1',
                'qty_sale_units' => 'required|numeric|min:0',
                'external_id' => 'required|integer',
            ]);

            // Fetch ItemSetting
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

            // Fetch Entity
            $entity = Entity::where('external_id', $validated['external_id'])->first();
            if (!$entity) {
                return response()->json([
                    'message' => 'Setup failed',
                    'success' => false,
                    'data' => 'No entity found for the given external_id'
                ], 400);
            }

            DB::beginTransaction();

            // Fetch Item
            $item = Item::where([
                'external_id' => $validated['external_id'],
                'external_item_id' => $validated['product_id'],
            ])->first();

            if (!$item) {
                return response()->json([
                    'message' => 'Item not found',
                    'success' => false,
                ], 404);
            }

            // Increment item quantity
            $item->increment('quantity', $validated['qty_sale_units']);

            // Step 1: Get latest bi-monthly average from MovingAverage
            $movingAverage = \App\Models\MovingAverage::where('item_id', $item->id)
                ->where('external_item_id', $validated['product_id'])
                ->where('external_id', $validated['external_id'])
                ->orderByDesc('calculated_at')
                ->first();

            $averageSales = 0;

            if ($movingAverage && floatval($movingAverage->bi_monthly_suom) > 0) {
                $averageSales = floatval($movingAverage->bi_monthly_suom);
            } else {
                $averageSales = floatval($itemSetting->daily_consumption);
            }

            // Step 2: Recalculate Safety & Buffer Stock
            $safetyStock = $itemSetting->safety_stock_days * $averageSales;
            $bufferStock = $itemSetting->buffer_stock_days * $averageSales;

            $itemSetting->update([
                'safety_stock' => $safetyStock,
                'buffer_stock' => $bufferStock,
            ]);

            // Step 3: Create Stock
            $stock = Stock::create([
                'entity_id' => $entity->id,
                'branch_id' => $validated['branch_id'],
                'item_id' => $item->id,
                'store_id' => $validated['store_id'],
                'batch_no' => $validated['batch_number'],
                'current_stock_suom' => $validated['qty_sale_units'],
                'opening_stock_suom' => $validated['qty_sale_units'],
                'closing_stock_suom' => $validated['qty_sale_units'],
                'date_of_delivery' => $validated['delivery_date'],
                'stock_aging_days' => 0,
                'lead_time' => Carbon::parse($validated['delivery_date'])->diffInDays(Carbon::parse($validated['order_date'])),
                'external_id' => $validated['external_id'],
                'suom' => $validated['suom'],
                'duom' => $validated['duom'],
                'suom_per_duom' => $validated['sale_units_per_delivery'],
                'suom_per_ouom' => $validated['sale_units_per_delivery'],
                'purchase_price' => $validated['purchase_price'],
                'no_of_sale_units_per_duom' => $validated['sale_units_per_delivery'],
                'qty_sale_units_purchased' => $validated['qty_sale_units'],
                'qty' => $validated['quantity'],
            ]);

            // Step 4: Update StockLevelDaysReport
            $stockLevelReport = StockLevelDaysReport::where('item_id', $item->id)->first();
            if ($stockLevelReport) {
                $productStockLevel = ProductStockLevel::where('item_id', $item->id)->first();

                $openingStock = $productStockLevel->opening_stock ?? 0;
                $purchasesToDate = $productStockLevel->deliveries_to_date + ($validated['quantity'] * $validated['sale_units_per_delivery']);
                $salesToDate = $productStockLevel->sales_to_date;
                $currentStockLevel = $openingStock + $purchasesToDate - $salesToDate;

                $stockLevelReport->update([
                    'current_stock_level' => $currentStockLevel,
                    'stock_level_days' => $averageSales > 0 ? intdiv($currentStockLevel, $averageSales) : 0,
                ]);

                $productStockLevel->update([
                    'deliveries_to_date' => $purchasesToDate,
                ]);
            }

            // Step 5: Update SystemStock
            $systemStock = SystemStock::where('item_id', $item->id)->first();
            if ($systemStock) {
                $systemStock->increment('purchases', $validated['quantity']);
            } else {
                SystemStock::create([
                    'entity_id' => $entity->id,
                    'external_item_id' => $validated['product_id'],
                    'item_id' => $item->id,
                    'external_id' => $validated['external_id'],
                    'opening_stock' => $itemSetting->opening_stock,
                    'purchases' => $validated['qty_sale_units'],
                    'sales' => 0,
                    'returns' => 0,
                    'date' => now(),
                ]);
            }

            // Step 6: Update CurrentStock
            $currentStock = CurrentStock::where('item_id', $item->id)->first();
            if ($currentStock) {
                $currentStock->increment('physical_stock', $validated['qty_sale_units']);
                $currentStock->increment('purchases', $validated['qty_sale_units']);
            } else {
                CurrentStock::create([
                    'entity_id' => $entity->id,
                    'item_id' => $item->id,
                    'external_item_id' => $validated['product_id'],
                    'external_id' => $validated['external_id'],
                    'physical_stock' => 0,
                    'purchases' => $validated['qty_sale_units'],
                    'sales' => 0,
                    'transfers' => 0,
                    'date' => now(),
                ]);
            }

            // Step 7: Update Stock Aging
            $today = Carbon::now();
            $lastDeliveryDate = Stock::where('item_id', $item->id)
                ->where('closing_stock_suom', '>', 0)
                ->orderBy('date_of_delivery', 'desc')
                ->value('date_of_delivery');

            if ($lastDeliveryDate) {
                $stockAgingDays = $today->diffInDays(Carbon::parse($lastDeliveryDate));
                $stock->update(['stock_aging_days' => $stockAgingDays]);
            } else {
                $stock->update(['stock_aging_days' => $today->diffInDays(Carbon::parse($validated['delivery_date']))]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Stock created successfully',
                'success' => true,
                'data' => [
                    'stock' => $stock,
                    'item' => $item,
                    'safety_stock' => $safetyStock,
                    'buffer_stock' => $bufferStock,
                ]
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $th) {
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
                'external_store_id' => 'required|integer', // Assuming this is needed for the store
                'price' => 'nullable|numeric|min:0', // Optional price for the sale
            ]);

            DB::beginTransaction();

            $entity = Entity::where('external_id', $validated['external_id'])->first();
            if (!$entity) {
                return response()->json([
                    'message' => 'Setup failed',
                    'success' => false,
                    'data' => 'No entity found for the given external_id'
                ], 400);
            }

            $item = Item::where([
                'external_item_id' => $entity->id,
                'entity_id' => $validated['external_id'],
                'external_store_id' => $validated['external_store_id'],
            ])->first();

            if (!$item) {
                return response()->json([
                    'message' => 'Item not found',
                    'success' => false,
                ], 404);
            }

            // Get SUOM per DUOM conversion ratio
            $suomPerDuom = $item->suom_per_duom;
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

            // Log the sale
            ItemSale::create([
                'item_id' => $item->id,
                'external_item_id' => $item->external_item_id,
                'entity_id' => $entity->id,
                'external_id' => $validated['external_id'],
                'quantity_suom' => $suomToReduce,
                'source' => 'api',
                'reference' => null,
                'remarks' => null,
                'sold_at' => now(),
                'external_store_id' => $validated['external_store_id'],
                'price' => $validated['price'] ?? null, // Optional price for the sale
            ]);

            // Calculate moving averages
            //$this->calculateMovingAverages($item->id, $validated['external_id'], $validated['external_store_id'], $entity->id);

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

    public function reduceStockBulk(Request $request)
    {
        try {
            $validated = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|integer',
                'items.*.quantity' => 'required|numeric|min:1', // Direct quantity to reduce
                'items.*.price' => 'nullable|numeric|min:0', // Optional price for the sale
                'external_id' => 'required|integer', // Moved to root level
                'external_store_id' => 'required|integer', // Moved to root level
            ]);

            DB::beginTransaction();

            $entity = Entity::where('external_id', $validated['external_id'])->first();
            if (!$entity) {
                return response()->json([
                    'message' => 'Setup failed',
                    'success' => false,
                    'data' => 'No entity found for the given external_id'
                ], 400);
            }

            foreach ($validated['items'] as $entry) {
                $item = Item::where([
                    'external_item_id' => $entry['product_id'],
                    'entity_id' => $entity->id,
                    'external_store_id' => $validated['external_store_id'], // Use root-level external_store_id
                ])->first();

                if (!$item) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Item not found for product ID: ' . $entry['product_id'],
                        'success' => false,
                    ], 404);
                }

                $quantityToReduce = $entry['quantity'];

                if ($item->quantity < $quantityToReduce) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Not enough stock for product ID: ' . $entry['product_id'],
                        'success' => false,
                        'available' => $item->quantity,
                        'requested' => $quantityToReduce
                    ], 400);
                }

                // Reduce quantity directly from item
                $item->decrement('quantity', $quantityToReduce);

                // Update Product Stock Level
                ProductStockLevel::where('item_id', $item->id)->increment('sales_to_date', $quantityToReduce);

                // Update Stock Level Report
                StockLevelDaysReport::where('external_item_id', $entry['product_id'])->decrement('current_stock_level', $quantityToReduce);

                // Log the sale
                ItemSale::create([
                    'item_id' => $item->id,
                    'external_item_id' => $item->external_item_id,
                    'entity_id' => $entity->id,
                    'external_id' => $validated['external_id'],
                    'quantity_suom' => $quantityToReduce,
                    'source' => 'api',
                    'reference' => null,
                    'sold_at' => now(),
                    'external_store_id' => $validated['external_store_id'],
                    'price' => $entry['price'] ?? null, // Optional price for the sale
                ]);

                // Calculate moving averages
                $this->calculateMovingAverages($item->id, $item->external_item_id, $validated['external_id'], $validated['external_store_id'], $entity->id);
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



    protected function calculateMovingAverages($itemId, $externalItemId, $externalId, $externalStoreId, $entityId)
    {
        $sales = ItemSale::where('item_id', $itemId)
            ->where('entity_id', $externalId)
            ->where('external_store_id', $externalStoreId)
            ->orderBy('sold_at', 'desc')
            ->get();

        // Bi-Monthly: Average 24-hour consumption for the past 15 days
        $biMonthlySales = $sales->where('sold_at', '>=', now()->subDays(15))->sum('quantity_suom') / 15;
        // Monthly: Average 24-hour consumption for the past 30 days
        $monthlySales = $sales->where('sold_at', '>=', now()->subDays(30))->sum('quantity_suom') / 30;
        // Quarterly: Average 24-hour consumption for the past 90 days
        $quarterlySales = $sales->where('sold_at', '>=', now()->subDays(90))->sum('quantity_suom') / 90;
        // Biannual: Average 24-hour consumption for the past 180 days
        $biannualSales = $sales->where('sold_at', '>=', now()->subDays(180))->sum('quantity_suom') / 180;
        // Annual: Average 24-hour consumption for the past 365 days
        $annualSales = $sales->where('sold_at', '>=', now()->subDays(365))->sum('quantity_suom') / 365;

        // Create a new record with a timestamp to ensure uniqueness
        MovingAverage::create([
            'item_id' => $itemId,
            'entity_id' => $entityId,
            'external_item_id' => $externalItemId,
            'external_id' => $externalId,
            'calculated_at' => Carbon::now(), // Unique timestamp for each calculation
            'bi_monthly_suom' => (string)$biMonthlySales,
            'monthly_suom' => (string)$monthlySales,
            'quarterly_suom' => (string)$quarterlySales,
            'biannual_suom' => (string)$biannualSales,
            'annual_suom' => (string)$annualSales,
        ]);
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

    public  function getStockByExternalId($externalId)
    {
        try {
            $store = Item::where('external_id', $externalId)
                ->with(['stocks'])
                ->get();
            return response()->json(['data' => $store, 'success' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching stores.', 'success' => false]);
        }
    }


    //ordering
    public function placeOrder(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer',
                'external_id' => 'required|integer',
                'quantity' => 'required|numeric|min:1', // Quantity to order in DUOM
                'external_store_id' => 'required|integer',
                'expected_delivery_date' => 'required|date|after_or_equal:today',
            ]);

            DB::beginTransaction();

            $item = Item::where([
                'external_item_id' => $validated['product_id'],
                'entity_id' => $validated['external_id'],
                'external_store_id' => $validated['external_store_id'],
            ])->first();

            if (!$item) {
                return response()->json([
                    'message' => 'Item not found',
                    'success' => false,
                ], 404);
            }

            $itemSetting = ItemSetting::where('external_item_id', $validated['product_id'])
                ->where('entity_id', $validated['external_id'])
                ->first();

            if (!$itemSetting) {
                return response()->json([
                    'message' => 'Item setting not found',
                    'success' => false,
                ], 400);
            }

            $reorderLevel = $itemSetting->safety_stock_days * $itemSetting->daily_consumption;
            $currentStock = $item->quantity;

            if ($currentStock >= $reorderLevel) {
                return response()->json([
                    'message' => 'Stock level sufficient, no need to order',
                    'success' => false,
                    'current_stock' => $currentStock,
                    'reorder_level' => $reorderLevel,
                ], 400);
            }

            $order = Order::create([
                'item_id' => $item->id,
                'external_id' => $validated['external_id'],
                'external_store_id' => $validated['external_store_id'],
                'quantity_ordered' => $validated['quantity'],
                'order_date' => now(),
                'expected_delivery_date' => $validated['expected_delivery_date'],
                'status' => 'pending',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Order placed successfully',
                'success' => true,
                'data' => $order,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to place order',
                'success' => false,
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function checkReorder(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer',
                'external_id' => 'required|integer',
                'external_store_id' => 'required|integer',
            ]);

            $item = Item::where([
                'external_item_id' => $validated['product_id'],
                'entity_id' => $validated['external_id'],
                'external_store_id' => $validated['external_store_id'],
            ])->first();

            if (!$item) {
                return response()->json([
                    'message' => 'Item not found',
                    'success' => false,
                ], 404);
            }

            $itemSetting = ItemSetting::where('external_item_id', $validated['product_id'])
                ->where('entity_id', $validated['external_id'])
                ->first();

            if (!$itemSetting) {
                return response()->json([
                    'message' => 'Item setting not found',
                    'success' => false,
                ], 400);
            }

            $reorderLevel = $itemSetting->safety_stock_days * $itemSetting->daily_consumption;
            $currentStock = $item->quantity;

            $needsReorder = $currentStock < $reorderLevel;

            return response()->json([
                'message' => 'Reorder check completed',
                'success' => true,
                'data' => [
                    'product_id' => $validated['product_id'],
                    'current_stock' => $currentStock,
                    'reorder_level' => $reorderLevel,
                    'needs_reorder' => $needsReorder,
                ]
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed to check reorder',
                'success' => false,
                'error' => $th->getMessage()
            ], 500);
        }
    }
    //odering
}
