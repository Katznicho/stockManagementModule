<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\StockTransfer;
use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    public function index() { return StockTransfer::all(); }
    public function store(Request $request) {
        $request->validate([
            'from_entity_id' => 'required|exists:entities,id',
            'from_branch_id' => 'required|exists:branches,id',
            'item_id' => 'required|exists:items,id',
            'from_store_id' => 'required|exists:stores,id',
            'to_store_id' => 'required|exists:stores,id',
            'qty_requested_suom' => 'required|numeric',
            'reason' => 'nullable|string',
            'requesting_user_id' => 'required|exists:entity_users,id'
        ]);
        return StockTransfer::create($request->all());
    }
    public function show($id) { return StockTransfer::findOrFail($id); }
    public function update(Request $request, $id) {
        $transfer = StockTransfer::findOrFail($id);
        $transfer->update($request->all());
        return $transfer;
    }
    public function destroy($id) { StockTransfer::destroy($id); return response()->noContent(); }
}
