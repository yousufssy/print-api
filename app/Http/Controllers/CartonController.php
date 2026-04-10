<?php

namespace App\Http\Controllers;

use App\Models\Carton;
use Illuminate\Http\{Request, JsonResponse};

class CartonController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $id   = $request->get('order_id');
        $year = $request->get('year', date('Y'));

        $q = Carton::where('year', $year);
        if ($id) $q->where('ID', $id);

        return response()->json($q->orderBy('ID1')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $c = Carton::create($request->all());
        return response()->json($c, 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(Carton::findOrFail($id));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $c = Carton::findOrFail($id);
        $c->update($request->all());
        return response()->json($c);
    }

    public function destroy(string $id): JsonResponse
    {
        Carton::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
