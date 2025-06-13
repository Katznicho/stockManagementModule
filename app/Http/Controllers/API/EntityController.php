<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Entity;
use App\Models\Item;
use App\Models\ItemSetting;
use App\Models\OrderSetting;
use App\Models\ProductStockLevel;
use App\Models\Stock;
use App\Models\StockLevelDaysReport;
use App\Models\Store;
use Illuminate\Http\Request;

//$jov*oP3f

class EntityController extends Controller
{
    public function index()
    {
        return Entity::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:entities',
            'logo' => 'nullable|url',
        ]);

        $entity = Entity::create($request->all());
        return response()->json($entity, 201);
    }



    public function moduleSetup(Request $request)
    {
        try {
            $request->validate([
                'entity_name' => 'required|string|max:255',
                'branch_name' => 'required|string|max:255',
                'store_name' => 'required|string|max:255',
                'external_id' => 'required',
                // Validate products array
                'products' => 'required|array',
                'products.*.product_id' => 'required',
                'products.*.product_name' => 'required|string|max:255',
                'products.*.daily_consumption' => 'required|numeric|min:0',
                'products.*.safety_stock_days' => 'required|integer|min:0',
                'products.*.buffer_stock' => 'required|integer|min:0',
                'products.*.opening_stock' => 'required|integer|min:0',
                'notification_to_order' => 'required|integer|min:1',
            ]);

            // Create entity
            $entity = Entity::create([
                'name' => $request->entity_name,
                'logo' => $request->logo,
                'external_id' => $request->external_id,
            ]);

            // Create branch
            $branch = Branch::create([
                'entity_id' => $entity->id,
                'name' => $request->branch_name,
                'external_id' => $request->external_id,
            ]);

            // Create store
            $store = Store::create([
                'branch_id' => $branch->id,
                'entity_id' => $entity->id,
                'parent_store_id' => null,
                'external_id' => $request->external_id,
                'level' => 1,
                'name' => $request->store_name,
                'notification_to_order' => $request->notification_to_order,
            ]);

            // Loop through products and save to the store
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
                    'quantity' => $product['opening_stock'], // Initial quantity in delivery units
                ]);

                ProductStockLevel::create([
                    'item_id' => $item->id, // ✅ Correct: use DB ID
                    'external_item_id' => $product['product_id'],
                    'opening_stock' => $product['opening_stock'] ?? 0,
                    'deliveries_to_date' => 0,
                    'sales_to_date' => 0,
                    'returns' => 0,
                    'external_id' =>$request->external_id,
                    'entity_id' => $entity->id,
                ]);

                StockLevelDaysReport::create([
                    'item_id' => $item->id, // ✅ Correct: use DB ID
                    'external_item_id' => $product['product_id'],
                    'current_stock_level' => $product['opening_stock'] ?? 0,
                    'daily_sales' => 0,
                    'average_sales' => 0,
                    'stock_level_days' => 0,
                    'entity_id' => $entity->id,
                    'external_id' =>$request->external_id,
                ]);

                //create stock for each item
                // Create Stock entry
                $stock = Stock::create([
                    'entity_id' => $entity->id,
                    // 'branch_id' => $vali,
                    'item_id' => $item->id,
                    'store_id' => $store->id,
                    'external_store_id'=>$product['external_store_id'],
                    'qty' => $product['opening_stock'], // Total quantity purchased in DUOM or SUOM
                ]);
                //create stock for each item


            }

            OrderSetting::create([
                'entity_id' => $entity->id,
                'notification_to_order' => $request->notification_to_order,
                'external_id' => $request->external_id,
            ]);

            return response()->json([
                'message' => 'Setup successful',
                'success' => true,
                'data' => $entity
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Setup failed',
                'success' => false,
                'data' => $e->getMessage()
            ], 500);
        }
    }


    public function show($id)
    {
        return Entity::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $entity = Entity::findOrFail($id);
        $entity->update($request->all());
        return response()->json($entity);
    }

    public function destroy($id)
    {
        Entity::destroy($id);
        return response()->json(null, 204);
    }
}
