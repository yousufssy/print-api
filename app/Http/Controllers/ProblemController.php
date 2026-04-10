<?php

namespace App\Http\Controllers;

use App\Models\Problem;
use Illuminate\Http\{Request, JsonResponse};

class ProblemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $id   = $request->get('order_id');
        $year = $request->get('year', date('Y'));

        $q = Problem::where('Year', $year);
        if ($id) $q->where('ID', $id);

        return response()->json($q->orderBy('_ID')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $p = Problem::create($request->all());
        return response()->json($p, 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(Problem::findOrFail($id));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $p = Problem::findOrFail($id);
        $p->update($request->all());
        return response()->json($p);
    }

    public function destroy(string $id): JsonResponse
    {
        Problem::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
