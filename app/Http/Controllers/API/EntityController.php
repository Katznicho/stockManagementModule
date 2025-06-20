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
use Illuminate\Support\Facades\DB;
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
        // Start a database transaction
        DB::beginTransaction();

        $request->validate([
            'entity_name' => 'required|string|max:255',
            'branch_name' => 'required|string|max:255',
            'store_name' => 'required|string|max:255',
            'store_id' => 'required|integer', // Validate branch store_id
            'external_id' => 'required',
            'notification_to_order' => 'required|integer|min:1',
            // Validate products array
            'products' => 'required|array',
            'products.*.product_id' => 'required',
            'products.*.product_name' => 'required|string|max:255',
            'products.*.daily_consumption' => 'required|numeric|min:0',
            'products.*.safety_stock_days' => 'required|integer|min:0',
            'products.*.buffer_stock' => 'required|integer|min:0',
            'products.*.opening_stock' => 'required|integer|min:0',
            'products.*.store_name' => 'required|string|max:255', // Validate product store_name
            'products.*.store_id' => 'required|integer', // Validate product store_id
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
            'external_store_id' => $request->store_id, // Use store_id from request
            'external_store_name' => $request->store_name, // Use store_name from request
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
                'external_id' =>$request->external_id,
                'quantity' => $product['opening_stock'], // Initial quantity in delivery units
                'item_setting_id' => $itemSetting->id,
                'store_id' => $store->id, // Associate item with the store
                'external_store_id' => $product['store_id'], // Use store_id from product
                'external_store_name' => $product['store_name'], // Use store_name from product
            ]);

            ProductStockLevel::create([
                'item_id' => $item->id, // 
                'external_item_id' => $product['product_id'],
                'opening_stock' => $product['opening_stock'] ?? 0,
                'deliveries_to_date' => 0,
                'sales_to_date' => 0,
                'returns' => 0,
                'external_id' => $request->external_id,
                'entity_id' => $entity->id,
            ]);

            StockLevelDaysReport::create([
                'item_id' => $item->id, // 
                'external_item_id' => $product['product_id'],
                'current_stock_level' => $product['opening_stock'] ?? 0,
                'daily_sales' => 0,
                'average_sales' => 0,
                'stock_level_days' => 0,
                'entity_id' => $entity->id,
                'external_id' => $request->external_id,
            ]);

            // Create Stock entry
            $stock = Stock::create([
                'entity_id' => $entity->id,
                'external_id' => $request->external_id,
                'branch_id' => $branch->id,
                'item_id' => $item->id,
                'store_id' => $store->id,
                'external_store_id' => $product['store_id'],
                'qty' => $product['opening_stock'], // Total quantity purchased in DUOM or SUOM
                'stock_aging_days'=> 0, // Initial stock aging days,
                'lead_time' => 0, // Initial lead time

            ]);
        }

        OrderSetting::create([
            'entity_id' => $entity->id,
            'notification_to_order' => $request->notification_to_order,
            'external_id' => $request->external_id,
        ]);

        // If all operations succeed, commit the transaction
        DB::commit();

        return response()->json([
            'message' => 'Setup successful',
            'success' => true,
            'data' => $entity
        ], 200);
    } catch (\Exception $e) {
        // If anything fails, roll back the transaction
        DB::rollBack();

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
