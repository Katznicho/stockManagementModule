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

// 'entity_id' => 'required|exists:entities,id',
// 'branch_name' => 'required|string|max:255',
// 'stock_name' => 'required|string|max:255',

public function moduleSetup(Request $request){
     try {
        $request->validate([
            'entity_name' =>'required|string|max:255',
            'branch_name' =>'required|string|max:255',
           'store_name' =>'required|string|max:255',
           'external_id' =>'required',
        ]);
        //create entity
        $entity = Entity::create([
            'name' => $request->entity_name,
            'logo' => $request->logo,
            'external_id' => $request->external_id,
        ]);
        //create branch
        $branch = Branch::create([
            'entity_id' => $entity->id,
            'name' => $request->branch_name,
            'external_id' => $request->external_id,
        ]);
        //create store
        $store = Store::create([
            'branch_id' => $branch->id,
            'entity_id' => $entity->id,
            'parent_store_id' => null,
            'external_id' => $request->external_id,
            'level' => 1,
            'name' => $request->store_name,
        ]);
        return response()->json(['message' => 'Setup successful','success'=>true, 'data'=>$entity ], 200);
         
     }
     catch (\Exception $e) {
        return response()->json(['message' => 'Setup failed', 'success'=>false , "data"=>$e->getMessage()], 500);
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
