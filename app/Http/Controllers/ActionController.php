<?php
namespace App\Http\Controllers;

use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActionController extends Controller
{
    private const ALLOWED_FIELDS = [
        'ID', 'year', 'Action', 'Color', 'Qunt_Ac', 'On', 'Machin',
        'Hours', 'Kelo', 'Actual', 'Tarkeb', 'Wash', 'Electricity',
        'Taghez', 'StopVar', 'Date', 'NotesA', 'Tabrer'
    ];

    private function cleanInput(array $data): array
    {
        // 1. إزالة الحقول المؤقتة من الـ Frontend
        $clean = array_diff_key($data, array_flip(['_isNew', 'ID1']));

        // 2. توحيد Year → year فقط
        if (isset($clean['Year']) && !isset($clean['year'])) {
            $clean['year'] = $clean['Year'];
            unset($clean['Year']);
        }

        // 3. الإبقاء على الحقول المسموحة فقط
        return array_intersect_key($clean, array_flip(self::ALLOWED_FIELDS));
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $year    = $request->get('year', date('Y'));
            $orderId = $request->get('ID');

            $query = DB::table('actions')
                ->where('year', $year)
                ->when($orderId, fn($q) => $q->where('ID', $orderId));

            return response()->json(
                $query->orderByDesc('ID1')->orderByDesc('ID')->limit(200)->get()
            );

        } catch (\Exception $e) {
            Log::error('Actions index error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $data = $this->cleanInput($request->all());

            if (empty($data['ID']) || empty($data['year'])) {
                return response()->json(['error' => 'Missing ID or year'], 422);
            }

            $id = DB::table('actions')->insertGetId($data);
            return response()->json(['ID1' => $id, 'ID' => $data['ID']], 201);

        } catch (\Exception $e) {
            Log::error('Actions store error', [
                'message' => $e->getMessage(),
                'input'   => $request->all()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $record = DB::table('actions')->where('ID1', $id)->first();
            return response()->json($record ?: ['error' => 'Not found'], $record ? 200 : 404);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $data = $this->cleanInput($request->all());
            unset($data['ID'], $data['year']); // لا نحدّث مفاتيح الربط

            $affected = DB::table('actions')
                ->where('ID1', $id)
                ->update($data);

            return response()->json(
                ['message' => $affected ? 'Updated' : 'Not found'],
                $affected ? 200 : 404
            );

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $affected = DB::table('actions')
                ->where('ID1', $id)
                ->delete();

            return response()->json(
                ['message' => $affected ? 'Deleted' : 'Not found'],
                $affected ? 200 : 404
            );

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
