<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\MovingAverage;
use App\Models\Stock;
use App\Models\ItemSetting;
use App\Models\Entity;
use App\Models\Branch;
use App\Models\Store;
use App\Models\ProductStockLevel;
use App\Models\StockLevelDaysReport;
use App\Models\OrderSetting;
use App\Models\ItemSale;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ItemController extends Controller
{
    public function reduceStock(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer',
                'external_id' => 'required|integer',
                'quantity' => 'required|numeric|min:1', // quantity in SUOM
                'external_store_id' => 'required|integer',
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

            $suomPerDuom = $item->suom_per_duom;
            if (!$suomPerDuom || $suomPerDuom <= 0) {
                return response()->json([
                    'message' => 'Conversion ratio (suom_per_duom) not found',
                    'success' => false,
                ], 400);
            }

            $suomToReduce = $validated['quantity'];
            $duomToReduce = $suomToReduce / $suomPerDuom;

            if ($item->quantity < $duomToReduce) {
                return response()->json([
                    'message' => 'Not enough stock in DUOM',
                    'success' => false,
                    'available_duom' => $item->quantity,
                    'requested_duom' => $duomToReduce
                ], 400);
            }

            $item->decrement('quantity', $duomToReduce);
            ProductStockLevel::where('item_id', $item->id)->increment('sales_to_date', $suomToReduce);
            StockLevelDaysReport::where('item_id', $item->id)->decrement('current_stock_level', $suomToReduce);

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
                'external_store_id' => $validated['external_store_id'],
            ]);

            $this->calculateMovingAverages($item->id, $validated['external_id'], $validated['external_store_id']);

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
                'items.*.external_id' => 'required|integer',
                'items.*.quantity' => 'required|numeric|min:1', // quantity in SUOM
                'items.*.external_store_id' => 'required|integer',
            ]);

            DB::beginTransaction();

            foreach ($validated['items'] as $entry) {
                $item = Item::where([
                    'external_item_id' => $entry['product_id'],
                    'entity_id' => $entry['external_id'],
                    'external_store_id' => $entry['external_store_id'],
                ])->first();

                if (!$item) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Item not found for product ID: ' . $entry['product_id'],
                        'success' => false,
                    ], 404);
                }

                $suomPerDuom = $item->suom_per_duom;
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
                    'external_store_id' => $entry['external_store_id'],
                ]);

                $this->calculateMovingAverages($item->id, $entry['external_id'], $entry['external_store_id']);
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
                'order_id' => 'nullable|integer|exists:orders,id', // Link to existing order
                'suom' => 'required|string',
                'sale_units_per_delivery' => 'required|numeric|min:1',
                'qty_sale_units' => 'required|numeric|min:0',
                'external_id' => 'required|integer',
            ]);

            DB::beginTransaction();

            $item = Item::where([
                'external_item_id' => $validated['product_id'],
                'entity_id' => $validated['external_id'],
            ])->first();

            $itemSetting = ItemSetting::where('external_item_id', $validated['product_id'])
                ->where('entity_id', $validated['external_id'])
                ->first();

            if ($item) {
                $item->increment('quantity', $validated['quantity']);
            } else {
                $item = Item::create([
                    'entity_id' => $validated['external_id'],
                    'external_item_id' => $validated['product_id'],
                    'name' => $itemSetting->name ?? 'Product ' . $validated['product_id'],
                    'item_code' => 'ITEM-' . $validated['product_id'] . '-' . time(),
                    'external_id' => $validated['external_id'],
                    'quantity' => $validated['quantity'],
                ]);
            }

            $order = null;
            if ($validated['order_id']) {
                $order = Order::find($validated['order_id']);
                if (!$order || $order->item_id != $item->id || $order->status !== 'pending') {
                    return response()->json([
                        'message' => 'Invalid or fulfilled order',
                        'success' => false,
                    ], 400);
                }
            }

            $stock = Stock::create([
                'entity_id' => $validated['external_id'],
                'branch_id' => $validated['branch_id'],
                'item_id' => $item->id,
                'store_id' => $validated['store_id'],
                'batch_no' => $validated['batch_number'],
                'current_stock_suom' => $validated['qty_sale_units'],
                'opening_stock_suom' => $validated['qty_sale_units'],
                'closing_stock_suom' => $validated['qty_sale_units'],
                'date_of_delivery' => $validated['delivery_date'],
                'stock_aging_days' => 0,
                'lead_time' => $order ? Carbon::parse($validated['delivery_date'])->diffInDays(Carbon::parse($order->order_date)) : 0,
                'external_id' => $validated['external_id'],
                'suom' => $validated['suom'],
                'duom' => $validated['duom'],
                'suom_per_duom' => $validated['sale_units_per_delivery'],
                'suom_per_ouom' => $validated['sale_units_per_delivery'],
                'purchase_price' => $validated['purchase_price'],
                'no_of_sale_units_per_duom' => $validated['sale_units_per_delivery'],
                'qty_sale_units_purchased' => $validated['qty_sale_units'],
                'qty' => $validated['quantity'],
                'order_date' => $order ? $order->order_date : null,
            ]);

            $stockLevelReport = StockLevelDaysReport::where('item_id', $item->id)->first();
            if ($stockLevelReport) {
                $productStockLevel = ProductStockLevel::where('item_id', $item->id)->first();
                $openingStock = $productStockLevel->opening_stock ?? 0;
                $purchasesToDate = $productStockLevel->deliveries_to_date + ($validated['quantity'] * $validated['sale_units_per_delivery']);
                $salesToDate = $productStockLevel->sales_to_date;
                $currentStockLevel = $openingStock + $purchasesToDate - $salesToDate;

                $stockLevelReport->update([
                    'current_stock_level' => $currentStockLevel,
                ]);
            }

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

            if ($order) {
                $order->update([
                    'status' => 'fulfilled',
                    'actual_delivery_date' => $validated['delivery_date'],
                ]);
            }

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
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create stock',
                'success' => false,
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function moduleSetup(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'entity_name' => 'required|string|max:255',
                'branch_name' => 'required|string|max:255',
                'store_name' => 'required|string|max:255',
                'store_id' => 'required|integer',
                'external_id' => 'required',
                'notification_to_order' => 'required|integer|min:1',
                'products' => 'required|array',
                'products.*.product_id' => 'required',
                'products.*.product_name' => 'required|string|max:255',
                'products.*.daily_consumption' => 'required|numeric|min:0',
                'products.*.safety_stock_days' => 'required|integer|min:0',
                'products.*.buffer_stock' => 'required|integer|min:0',
                'products.*.opening_stock' => 'required|integer|min:0',
                'products.*.store_name' => 'required|string|max:255',
                'products.*.store_id' => 'required|integer',
            ]);

            $entity = Entity::create([
                'name' => $request->entity_name,
                'logo' => $request->logo,
                'external_id' => $request->external_id,
            ]);

            $branch = Branch::create([
                'entity_id' => $entity->id,
                'name' => $request->branch_name,
                'external_id' => $request->external_id,
            ]);

            $store = Store::create([
                'branch_id' => $branch->id,
                'entity_id' => $entity->id,
                'parent_store_id' => null,
                'external_store_id' => $request->store_id,
                'external_store_name' => $request->store_name,
                'external_id' => $request->external_id,
                'level' => 1,
                'name' => $request->store_name,
                'notification_to_order' => $request->notification_to_order,
            ]);

            foreach ($request->products as $product) {
                $itemSetting = ItemSetting::create([
                    'entity_id' => $entity->id,
                    'external_id' => $request->external_id,
                    'external_item_id' => $product['product_id'],
                    'name' => $product['product_name'],
                    'daily_consumption' => $product['daily_consumption'],
                    'safety_stock_days' => $product['safety_stock_days'],
                    'buffer_stock' => $product['buffer_stock'],
                    'opening_stock' => $product['opening_stock'],
                ]);

                $item = Item::create([
                    'entity_id' => $entity->id,
                    'external_item_id' => $product['product_id'],
                    'name' => $itemSetting->name ?? 'Product ' . $product['product_id'],
                    'item_code' => 'ITEM-' . $product['product_id'] . '-' . time(),
                    'external_id' => $entity->id,
                    'quantity' => $product['opening_stock'],
                    'item_setting_id' => $itemSetting->id,
                    'store_id' => $store->id,
                    'external_store_id' => $product['store_id'],
                    'external_store_name' => $product['store_name'],
                ]);

                ProductStockLevel::create([
                    'item_id' => $item->id,
                    'external_item_id' => $product['product_id'],
                    'opening_stock' => $product['opening_stock'] ?? 0,
                    'deliveries_to_date' => 0,
                    'sales_to_date' => 0,
                    'returns' => 0,
                    'external_id' => $request->external_id,
                    'entity_id' => $entity->id,
                ]);

                StockLevelDaysReport::create([
                    'item_id' => $item->id,
                    'external_item_id' => $product['product_id'],
                    'current_stock_level' => $product['opening_stock'] ?? 0,
                    'daily_sales' => 0,
                    'average_sales' => 0,
                    'stock_level_days' => 0,
                    'entity_id' => $entity->id,
                    'external_id' => $request->external_id,
                ]);

                Stock::create([
                    'entity_id' => $entity->id,
                    'external_id' => $request->external_id,
                    'branch_id' => $branch->id,
                    'item_id' => $item->id,
                    'store_id' => $store->id,
                    'external_store_id' => $product['store_id'],
                    'qty' => $product['opening_stock'],
                    'stock_aging_days' => 0,
                    'lead_time' => 0,
                ]);
            }

            OrderSetting::create([
                'entity_id' => $entity->id,
                'notification_to_order' => $request->notification_to_order,
                'external_id' => $request->external_id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Setup successful',
                'success' => true,
                'data' => $entity
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Setup failed',
                'success' => false,
                'data' => $e->getMessage()
            ], 500);
        }
    }

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

    protected function calculateMovingAverages($itemId, $externalId, $externalStoreId)
    {
        $sales = ItemSale::where('item_id', $itemId)
            ->where('entity_id', $externalId)
            ->where('external_store_id', $externalStoreId)
            ->orderBy('sold_at', 'desc')
            ->get();

        $biMonthlySales = $sales->where('sold_at', '>=', now()->subDays(15))->sum('quantity_suom') / 15;
        $monthlySales = $sales->where('sold_at', '>=', now()->subDays(30))->sum('quantity_suom') / 30;
        $quarterlySales = $sales->where('sold_at', '>=', now()->subDays(90))->sum('quantity_suom') / 90;
        $biannualSales = $sales->where('sold_at', '>=', now()->subDays(180))->sum('quantity_suom') / 180;
        $annualSales = $sales->where('sold_at', '>=', now()->subDays(365))->sum('quantity_suom') / 365;

        MovingAverage::updateOrCreate(
            ['item_id' => $itemId, 'external_id' => $externalId, 'external_store_id' => $externalStoreId],
            [
                'entity_id' => $externalId,
                'external_item_id' => $sales->first()->external_item_id ?? null,
                'bi_monthly_suom' => (string)$biMonthlySales,
                'monthly_suom' => (string)$monthlySales,
                'quarterly_suom' => (string)$quarterlySales,
                'biannual_suom' => (string)$biannualSales,
                'annual_suom' => (string)$annualSales,
            ]
        );
    }


    
}