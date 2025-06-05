<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index() { return Store::all(); }
    public function store(Request $request) {
        $request->validate([
            'entity_id' => 'required|exists:entities,id',
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string',
            'level' => 'required|string',
            'parent_store_id' => 'nullable|exists:stores,id'
        ]);
        return Store::create($request->all());
    }
    public function show($id) { return Store::findOrFail($id); }
    public function update(Request $request, $id) {
        $store = Store::findOrFail($id);
        $store->update($request->all());
        return $store;
    }
    public function destroy($id) { Store::destroy($id); return response()->noContent(); }

    public function getStoresByEntityId($entityId) {
        // return Store::where('entity_id', $entityId)->get();
         try {
            $stores = Store::where('entity_id', $entityId)->get();
            return response()->json(['data' => $stores, 'success' => true]);
         }
         catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching stores.', 'success' => false]);
         }
    }

    public function getStoresByBranchId($branchId) {
        try {
            $stores = Store::where('branch_id', $branchId)->get();
            return response()->json(['data' => $stores,'success' => true]);
        }
        catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching stores.','success' => false]);
        }
    }

    public  function getStoreByExternalId($externalId) {
        try {
            $store = Store::where('external_id', $externalId)
             ->with(['branch'])
            ->get();
            return response()->json(['data' => $store,'success' => true]);
        }
        catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching stores.','success' => false]);
        }
    }
}
