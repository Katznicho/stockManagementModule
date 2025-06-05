<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index() { return Item::all(); }

    public function store(Request $request) {
         try {
            //code...
            $request->validate([
                // 'entity_id' => 'required',
                'name' => 'required|string',
                'external_id' =>'required|integer',
                // 'item_code' => 'required|string',
                // 'suom' => 'required|string',
                // 'duom' => 'required|string',
                // 'ouom' => 'required|string',
                // 'suom_per_duom' => 'required|integer',
                // 'suom_per_ouom' => 'required|integer',
                // 'purchase_price' => 'required|numeric',
            ]);
            // $items =  Item::create([

            // ])
            // return Item::create($request->all());
         } catch (\Throwable $th) {
            //throw $th;
            return  response()->json([
                'message' => $th->getMessage()
            ], 500);
         }

    }
    public function show($id) { return Item::findOrFail($id); }
    public function update(Request $request, $id) {
        $item = Item::findOrFail($id);
        $item->update($request->all());
        return $item;
    }
    public function destroy($id) { Item::destroy($id); return response()->noContent(); }
}
