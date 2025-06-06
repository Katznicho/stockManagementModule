<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Entity;
use App\Models\Store;
use Illuminate\Http\Request;

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
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.product_name' => 'required|string|max:255',
            'products.*.batch_no' => 'required|string|max:255',
            'products.*.duom' => 'required|string|max:255',
            'products.*.suom' => 'required|string|max:255',
            'products.*.sale_units_per_delivery_unit' => 'required|integer|min:1',
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
            Item::create([
                'entity_id' => $entity->id,
                'name' => $product['product_name'],
                'item_code' => $product['product_id'], // Assuming product_id is the item code
                'suom' => $product['suom'],
                'duom' => $product['duom'],
                'ouom' => $product['ouom'] ?? null,
                'suom_per_duom' => $product['suom_per_duom'] ?? null,
                'suom_per_ouom' => $product['suom_per_ouom'] ?? null,
                'purchase_price' => null, // Assuming purchase price is not provided in the request
                'external_id' => $request->external_id,
                'external_item_id' => $product['product_id'], // Assuming product_id is the external item ID
                'quantity' =>  $product['opening_stock'], // Initial quantity set to 0
                'date_of_delivery' => now(), // Assuming current date for delivery
                'batch_no' => $product['batch_no'],
                'sale_units_per_delivery_unit' => $product['sale_units_per_delivery_unit'],
                'daily_consumption' => $product['daily_consumption'],
                'safety_stock_days' => $product['safety_stock_days'],
                'buffer_stock' => $product['buffer_stock'],
                'opening_stock' => $product['opening_stock']
            ])
            ;
            //we need to calulate the stock levels here per product

            ProductStockLevel::create([
                'item_id' => $product['product_id'], // Assuming product_id is the item ID
                'external_item_id' => $product['product_id'], // Assuming product_id is the external item ID
                'opening_stock' => $product['opening_stock'],
                'deliveries_to_date' => 0, // Initial deliveries set to 0
                'sales_to_date' => 0, // Initial sales set to 0
                'returns' => 0, // Initial returns set to 0
                'external_id' => $request->external_id,
            ]);

            //Add product the stock level days Report

            StockLevelDaysReport::create([
                'item_id' => $product['product_id'], // Assuming product_id is the item ID
                'external_item_id' => $product['product_id'], // Assuming product_id is the external item ID
                'current_stock_level' => $product['opening_stock'],
                'daily_sales' => 0, // Initial daily sales set to 0
                'average_sales' => 0, // Initial average sales set to 0
                'stock_level_days' => 0, // Initial stock level days set to 0
            ]);
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
