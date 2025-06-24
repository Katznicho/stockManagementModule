<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ItemSale;
use Illuminate\Http\Request;

class ItemSaleController extends Controller
{

    public  function getSalesByExternalId($externalId)
    {
        if (!$externalId) {
            return response()->json(['message' => 'External ID is required.', 'success' => false]);
        }
        try {
            $store = ItemSale::where('external_id', $externalId)
                ->with(['item'])
                ->get();
            return response()->json(['data' => $store, 'success' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching stores.', 'success' => false]);
        }
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
