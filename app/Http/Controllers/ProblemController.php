<?php

namespace App\Http\Controllers;

use App\Models\Problem;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\Log;

class ProblemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            // ✅ استخدم 'ID' بدل 'order_id' لتتناسب مع Frontend
            $id   = $request->get('ID');  // ← غيّر من 'order_id' إلى 'ID'
            $year = $request->get('Year', date('Y'));  // ← غيّر من 'year' إلى 'Year'

            $q = Problem::where('Year', $year);
            if ($id) $q->where('ID', $id);

            // ✅ استخدم primaryKey الصحيح (افترضنا أنه 'ID1')
            return response()->json($q->orderBy('ID1')->get());

        } catch (\Exception $e) {
            Log::error('Problem index error', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->except(['_isNew', '_rowId']);

            // ✅ تأكد من وجود الحقول الإلزامية
            if (empty($data['ID']) || empty($data['Year'])) {
                return response()->json(['error' => 'Missing ID or Year'], 422);
            }

            $p = Problem::create($data);
            return response()->json($p, 201);

        } catch (\Exception $e) {
            Log::error('Problem store error', [
                'message' => $e->getMessage(),
                'input' => $request->all()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            // ✅ استخدم primaryKey الصحيح
            return response()->json(Problem::findOrFail($id));
        } catch (\Exception $e) {
            Log::error('Problem show error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Not found'], 404);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $p = Problem::findOrFail($id);
            $data = $request->except(['_isNew', '_rowId', 'ID', 'Year', 'ID1']);

            $p->update($data);
            return response()->json($p);

        } catch (\Exception $e) {
            Log::error('Problem update error', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $p = Problem::findOrFail($id);
            $p->delete();
            return response()->json(['message' => 'Deleted.']);
        } catch (\Exception $e) {
            Log::error('Problem destroy error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Not found'], 404);
        }
    }
}
