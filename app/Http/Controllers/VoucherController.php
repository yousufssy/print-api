<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VoucherController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $id   = $request->get('order_id');
        $Year = $request->get('Year', date('Y'));

        $q = Voucher::where('Year', $Year);
        if ($id) $q->where('ID', $id);

        return response()->json($q->orderBy('ID')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $v = Voucher::create($request->all());
        return response()->json($v, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $v = Voucher::findOrFail($id);
        $v->update($request->all());
        return response()->json($v);
    }

    public function destroy(string $id): JsonResponse
    {
        Voucher::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(Voucher::findOrFail($id));
    }
}
