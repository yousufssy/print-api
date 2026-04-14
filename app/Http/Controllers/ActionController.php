<?php
namespace App\Http\Controllers;

use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActionController extends Controller
{
    /**
     * ✅ قائمة الحقول المسموح بإدخالها/تحديثها
     */
    private const FILLABLE = [
        'order_id', 'year',           // مفاتيح الربط
        'Action', 'Color',            // بيانات العملية
        'Qunt_Ac', 'On', 'Machin',
        'Hours', 'Kelo', 'Actual',
        'Tarkeb', 'Wash', 'Electricity',
        'Taghez', 'StopVar', 'Date',
        'NotesA', 'Tabrer'
    ];

    /**
     ✅ تصفية البيانات: إزالة الحقول المؤقتة وتوحيد أسماء الأعمدة
     */
    private function sanitizeInput(array $data): array
    {
        // 1. إزالة الحقول المؤقتة من الـ Frontend
        $filtered = array_diff_key($data, array_flip(['_isNew', '_rowId', '_ID']));
        
        // 2. توحيد أسماء الحقول: ID (frontend) → order_id (database)
        if (isset($filtered['ID']) && !isset($filtered['order_id'])) {
            $filtered['order_id'] = $filtered['ID'];
            unset($filtered['ID']);
        }
        
        // 3. توحيد سنة: Year → year
        if (isset($filtered['Year']) && !isset($filtered['year'])) {
            $filtered['year'] = $filtered['Year'];
            unset($filtered['Year']);
        }
        
        // 4. الإبقاء على الحقول المسموحة فقط
        return array_intersect_key($filtered, array_flip(self::FILLABLE));
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $year = $request->get('year', date('Y'));
            $orderId = $request->get('order_id');
            
            // ✅ استخدام lowercase 'year' و 'order_id' لتطابق الداتابيز
            $q = DB::table('actions')
                ->where('year', $year)
                ->when($orderId, fn($query) => $query->where('order_id', $orderId));
                
            return response()->json($q->orderByDesc('_ID')->limit(100)->get());
            
        } catch (\Exception $e) {
            // 📝 تسجيل الخطأ للمساعدة في التشخيص
            \Log::error('Actions index failed', [
                'error' => $e->getMessage(),
                'params' => $request->only(['order_id', 'year'])
            ]);
            return response()->json(['message' => 'Server error'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $data = $this->sanitizeInput($request->all());
            
            // ✅ تحقق إلزامي من الحقول الأساسية
            if (empty($data['order_id']) || empty($data['year'])) {
                throw ValidationException::withMessages([
                    'order_id' => ['رقم الطلب مطلوب'],
                    'year' => ['سنة العمل مطلوبة']
                ]);
            }
            
            $id = DB::table('actions')->insertGetId($data);
            return response()->json(['_ID' => $id], 201); // ✅ إرجاع _ID ليتطابق مع الـ Frontend
            
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Actions store failed', [
                'error' => $e->getMessage(),
                'input' => $request->all()
            ]);
            return response()->json(['message' => 'Server error'], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $action = DB::table('actions')->where('_ID', $id)->first(); // ✅ استخدام _ID كمفتاح أساسي
            if (!$action) {
                return response()->json(['message' => 'Not found'], 404);
            }
            return response()->json($action);
        } catch (\Exception $e) {
            \Log::error('Actions show failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Server error'], 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $data = $this->sanitizeInput($request->all());
            // ✅ إزالة مفاتيح الربط من التحديث لتجنب الأخطاء
            unset($data['order_id'], $data['year']);
            
            $affected = DB::table('actions')->where('_ID', $id)->update($data);
            if ($affected === 0) {
                return response()->json(['message' => 'Not found or no changes'], 404);
            }
            return response()->json(['message' => 'Updated.']);
        } catch (\Exception $e) {
            \Log::error('Actions update failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Server error'], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $affected = DB::table('actions')->where('_ID', $id)->delete();
            if ($affected === 0) {
                return response()->json(['message' => 'Not found'], 404);
            }
            return response()->json(['message' => 'Deleted.']);
        } catch (\Exception $e) {
            \Log::error('Actions destroy failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Server error'], 500);
        }
    }
}
