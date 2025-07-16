<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shrinkage;
use App\Models\Entity;

class Shrinkagecontroller extends Controller
{

       public function getShrinkagesByExternalId($external_id)
{
    try {
        $entity = Entity::where('external_id', $external_id)->first();

        if (!$entity) {
            return response()->json([
                'message' => 'Entity not found',
                'success' => false,
            ], 404);
        }

        $shrinkages = Shrinkage::with(['item', 'branch', 'store'])
            ->where('entity_id', $entity->id)
            ->orderBy('stock_take_date', 'desc')
            ->get();

        return response()->json([
            'message' => 'Shrinkages retrieved successfully',
            'success' => true,
            'data' => $shrinkages
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'message' => 'Failed to retrieve shrinkages',
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
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
