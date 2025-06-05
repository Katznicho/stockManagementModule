<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index() { return Supplier::all(); }
    public function store(Request $request) {
        $request->validate([
            'name' => 'required|string',
            'contact_info' => 'nullable|string',
            'lead_time_days' => 'nullable|integer'
        ]);
        return Supplier::create($request->all());
    }
    public function show($id) { return Supplier::findOrFail($id); }
    public function update(Request $request, $id) {
        $supplier = Supplier::findOrFail($id);
        $supplier->update($request->all());
        return $supplier;
    }
    public function destroy($id) { Supplier::destroy($id); return response()->noContent(); }
}
