<?php
namespace App\Http\Controllers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\DB;

class ActionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $year = $request->get('year', date('Y'));
        $id   = $request->get('order_id');
        $q = DB::table('actions')->where('Year', $year);
        if ($id) $q->where('ID', $id);
        return response()->json($q->orderByDesc('ID')->limit(100)->get());
    }
    public function store(Request $request): JsonResponse
    {
        $id = DB::table('actions')->insertGetId($request->all());
        return response()->json(['ID' => $id], 201);
    }
    public function show(string $id): JsonResponse
    {
        return response()->json(DB::table('actions')->where('ID', $id)->first());
    }
    public function update(Request $request, string $id): JsonResponse
    {
        DB::table('actions')->where('ID', $id)->update($request->all());
        return response()->json(['message' => 'Updated.']);
    }
    public function destroy(string $id): JsonResponse
    {
        DB::table('actions')->where('ID', $id)->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
