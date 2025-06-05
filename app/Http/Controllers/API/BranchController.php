<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        return Branch::all();
    }
    public function store(Request $request)
    {
        try {
            //code...
            $request->validate(['entity_id' => 'required|exists:entities,id', 'name' => 'required|string', 'external_id' =>'required|integer']);
            $branch  = Branch::create([
                'entity_id' => $request->entity_id,
                'name' => $request->name,
                'external_id' => $request->external_id,
            ]);
            return response()->json(['data' => $branch,'success' => true], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(['data' => $th->getMessage(),'success' => false], 500);
        }


    }
    public function show($id)
    {
        return Branch::findOrFail($id);
    }
    public function update(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);
        $branch->update($request->all());
        return $branch;
    }
    public function destroy($id)
    {
        Branch::destroy($id);
        return response()->noContent();
    }

    //get branch by external_id
    public function getBranchesByExternalId($external_id)
    {
        try {
            //code...
            $branches =  Branch::where('external_id', $external_id)->get();

            return response()->json(['data' => $branches, 'success' => true], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(['data' => $th->getMessage(), 'success' => false], 500);
        }
    }
}
