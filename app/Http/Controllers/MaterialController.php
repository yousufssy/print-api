<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\{Request, JsonResponse};

class MaterialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $id   = $request->get('order_id');
        $Year = $request->get('Year', date('Y'));

        $q = Material::where('Year', $Year);
        if ($id) $q->where('ID', $id);

        return response()->json($q->orderBy('_ID')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $m = Material::create($request->all());
        return response()->json($m, 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(Material::findOrFail($id));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $m = Material::findOrFail($id);
        $m->update($request->all());
        return response()->json($m);
    }

    public function destroy(string $id): JsonResponse
    {
        Material::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
