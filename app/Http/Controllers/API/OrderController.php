<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index() { return Order::all(); }
    public function store(Request $request) {
        $request->validate([
            'entity_id' => 'required|exists:entities,id',
            'branch_id' => 'required|exists:branches,id',
            'item_id' => 'required|exists:items,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'order_qty_suom' => 'required|numeric',
            'order_qty_ouom' => 'required|numeric',
            'order_amount_ugx' => 'required|numeric',
            'order_date' => 'required|date',
            'status' => 'required|string'
        ]);
        return Order::create($request->all());
    }
    public function show($id) { return Order::findOrFail($id); }
    public function update(Request $request, $id) {
        $order = Order::findOrFail($id);
        $order->update($request->all());
        return $order;
    }
    public function destroy($id) { Order::destroy($id); return response()->noContent(); }
}
