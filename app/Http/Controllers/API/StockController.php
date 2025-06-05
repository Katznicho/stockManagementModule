<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index() { return Stock::all(); }
    public function store(Request $request) {
        $request->validate([
            'entity_id' => 'required|exists:entities,id',
            'branch_id' => 'required|exists:branches,id',
            'item_id' => 'required|exists:items,id',
            'store_id' => 'required|exists:stores,id',
            'batch_no' => 'required|string',
            'current_stock_suom' => 'required|numeric'
        ]);
        return Stock::create($request->all());
    }
    public function show($id) { return Stock::findOrFail($id); }
    public function update(Request $request, $id) {
        $stock = Stock::findOrFail($id);
        $stock->update($request->all());
        return $stock;
    }
    public function destroy($id) { Stock::destroy($id); return response()->noContent(); }
}
