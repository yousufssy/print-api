<?php
namespace App\Http\Controllers;

use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActionController extends Controller
{
    /**
     * ✅ الحقول المسموح بها فقط (للحماية من الحقول الزائدة)
     */
    private const ALLOWED_FIELDS = [
        'order_id', 'year',
        'Action', 'Color', 'Qunt_Ac', 'On', 'Machin',
        'Hours', 'Kelo', 'Actual', 'Tarkeb', 'Wash',
        'Electricity', 'Taghez', 'StopVar', 'Date',
        'NotesA', 'Tabrer'
    ];

    /**
     * ✅ تنظيف البيانات: إزالة الحقول المؤقتة وتوحيد الأسماء
     */
    private function cleanInput(array $data): array
    {
        // 1. إزالة الحقول المؤقتة من الـ Frontend
        $clean = array_diff_key($data, array_flip(['_isNew', '_rowId', '_ID']));
        
        // 2. توحيد: ID (frontend) → order_id (database)
        if (isset($clean['ID']) && !isset($clean['order_id'])) {
            $clean['order_id'] = $clean['ID'];
            unset($clean['ID']);
        }
        
        // 3. توحيد: Year → year
        if (isset($clean['Year']) && !isset($clean['year'])) {
            $clean['year'] = $clean['Year'];
            unset($clean['Year']);
        }
        
        // 4. الإبقاء على الحقول المسموحة فقط
        return array_intersect_key($clean, array_flip(self::ALLOWED_FIELDS));
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $year = $request->get('year', date('Y'));
            $orderId = $request->get('order_id');
            
            // ✅ lowercase 'year' و 'order_id' لتطابق الداتابيز
            $query = DB::table('actions')
                ->where('year', $year);
                
            if ($orderId) {
                $query->where('order_id', $orderId);
            }
            
            return response()->json(
                $query->orderByDesc('_ID')->limit(200)->get()
            );
            
        } catch (\Exception $e) {
            Log::error('Actions index error', [
                'message' => $e->getMessage(),
                'params' => $request->only(['order_id', 'year']),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $data = $this->cleanInput($request->all());
            
            // تحقق إلزامي
            if (empty($data['order_id']) || empty($data['year'])) {
                return response()->json([
                    'message' => 'Missing required fields',
                    'required' => ['order_id', 'year']
                ], 422);
            }
            
            $id = DB::table('actions')->insertGetId($data);
            
            // ✅ إرجاع _ID ليتطابق مع توقعات الـ Frontend
            return response()->json(['_ID' => $id, 'ID' => $id], 201);
            
        } catch (\Exception $e) {
            Log::error('Actions store error', [
                'message' => $e->getMessage(),
                'input' => $request->all(),
                'cleaned' => $this->cleanInput($request->all())
            ]);
            return response()->json(['message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $record = DB::table('actions')->where('_ID', $id)->first();
            if (!$record) {
                return response()->json(['message' => 'Not found'], 404);
            }
            return response()->json($record);
        } catch (\Exception $e) {
            Log::error('Actions show error', ['id' => $id, 'message' => $e->getMessage()]);
            return response()->json(['message' => 'Server error'], 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $data = $this->cleanInput($request->all());
            
            // إزالة مفاتيح الربط من التحديث
            unset($data['order_id'], $data['year']);
            
            $affected = DB::table('actions')->where('_ID', $id)->update($data);
            
            if ($affected === 0) {
                return response()->json(['message' => 'Not found or no changes'], 404);
            }
            return response()->json(['message' => 'Updated']);
            
        } catch (\Exception $e) {
            Log::error('Actions update error', ['id' => $id, 'message' => $e->getMessage()]);
            return response()->json(['message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $affected = DB::table('actions')->where('_ID', $id)->delete();
            
            if ($affected === 0) {
                return response()->json(['message' => 'Not found'], 404);
            }
            return response()->json(['message' => 'Deleted']);
            
        } catch (\Exception $e) {
            Log::error('Actions destroy error', ['id' => $id, 'message' => $e->getMessage()]);
            return response()->json(['message' => 'Server error'], 500);
        }
    }
}
