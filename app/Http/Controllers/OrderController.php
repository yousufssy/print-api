<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    private const PER_PAGE = 50;

    /**
     * 🔍 البحث المتقدم - يدعم الفلاتر المتعددة
     */
    public function advancedSearch(Request $request): JsonResponse
    {
        try {
            $query = $this->buildSearchQuery($request->all());

            // 🔄 منطق الترتيب (Sorting)
            $allowedSorts = ['ID', 'Ser', 'date_come', 'Apoent_Delv_date', 'Demand', 'Price', 'Customer', 'Year'];
            $sortBy    = in_array($request->get('sortBy', 'ID'), $allowedSorts) ? $request->get('sortBy', 'ID') : 'ID';
            $sortOrder = in_array($request->get('sortOrder', 'desc'), ['asc', 'desc']) ? $request->get('sortOrder', 'desc') : 'desc';

            $query->orderBy($sortBy, $sortOrder);

            // 📄 الترقيم (Pagination)
            $page  = max((int) $request->get('page', 1), 1);
            $limit = min((int) $request->get('limit', self::PER_PAGE), 200);

            $total  = (clone $query)->count();
            $orders = $query->skip(($page - 1) * $limit)->take($limit)->get();
            
            // ✅ حساب totalPages للتوافق مع Frontend
            $totalPages = ceil($total / $limit);

            return response()->json([
                'data'       => $orders,
                'total'      => $total,
                'page'       => $page,
                'last_page'  => $totalPages,
                'totalPages' => $totalPages, // ✅ إضافة للتوافق
            ]);
        } catch (\Exception $e) {
            Log::error("خطأ في البحث المتقدم: " . $e->getMessage());
            return response()->json(['error' => 'حدث خطأ أثناء معالجة البحث'], 500);
        }
    }

    /**
     * بناء الاستعلام ديناميكياً بناءً على الحقول المعبأة فقط
     */
   protected function buildSearchQuery(array $filters)
{
    $query = Order::query();

    // بحث برقم الطلب (مطابقة تامة)
    if (!empty($filters['ID'])) {
        $query->where('ID', $filters['ID']);
    }

    // بحث باسم العميل (بحث جزئي)
    if (!empty($filters['Customer'])) {
        $query->where('Customer', 'LIKE', '%' . $filters['Customer'] . '%');
    }

     // فلترة حسب البيان / الطلب (بحث جزئي)
        if (!empty($filters['Demand'])) {
            $query->where('Demand', 'LIKE', '%' . $filters['Demand'] . '%');
        }

        // ✅ فلترة حسب السنة - قبول كلاً من Year و year
        if (!empty($filters['Year'])) {
            $query->where('Year', $filters['Year']);
        } elseif (!empty($filters['year'])) {
            $query->where('Year', $filters['year']);
        }

      
        // فلترة حسب حالة الطباعة (Boolean)
        if (isset($filters['Printed']) && $filters['Printed'] !== '') {
            $query->where('Printed', $filters['Printed']);
        }

        // فلترة حسب حالة الفوترة (Boolean)
        if (isset($filters['Billed']) && $filters['Billed'] !== '') {
            $query->where('Billed', $filters['Billed']);
        }

        // فلترة حسب التاريخ (نطاق زمني)
        if (!empty($filters['date_from'])) {
            $query->where('date_come', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('date_come', '<=', $filters['date_to']);
        }

        return $query;
    }

    /**
     * عرض قائمة الطلبات (الرئيسية)
     */
    public function index(Request $request): JsonResponse
    {
        $year = $request->get('year', date('Y'));
        $q    = $request->get('q');

        $query = Order::where('Year', $year);

        if ($q) {
            $query->where(function ($query) use ($q) {
                $query->where('Customer', 'like', "%$q%")
                      ->orWhere('Demand', 'like', "%$q%")
                      ->orWhere('ID', $q);
            });
        }

        $orders = $query->orderByDesc('ID')->paginate(self::PER_PAGE);
        return response()->json($orders);
    }

    /**
     * إنشاء طلب جديد
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        // معالجة حقول الـ Boolean لتحويلها لـ 0 أو 1
        $booleanFields = [
            'Printed', 'Billed', 'DubelM', 'varnich', 'uv_Spot', 'uv',
            'seluvan_lum', 'seluvan_mat', 'Tay', 'Tad3em', 'harary',
            'rolling', 'rollingBack', 'Reseved'
        ];

        foreach ($booleanFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }
        }

        $order = Order::create($data);
        return response()->json($order, 201);
    }

    /**
     * عرض تفاصيل طلب واحد مع السندات المرتبطة
     */
    public function show($id, $year): JsonResponse
    {
        $order = Order::where('ID', $id)
                      ->where('Year', $year)
                      ->firstOrFail();
        
        $order->load('vouchers');
        
        return response()->json($order);
    }

    /**
     * تحديث بيانات الطلب
     */
    public function update(Request $request, $id, $year): JsonResponse
    {
        $order = Order::where('ID', $id)
                      ->where('Year', $year)
                      ->firstOrFail();

        $data = $request->all();

        $booleanFields = [
            'Printed', 'Billed', 'DubelM', 'varnich', 'uv_Spot', 'uv',
            'seluvan_lum', 'seluvan_mat', 'Tay', 'Tad3em', 'harary',
            'rolling', 'rollingBack', 'Reseved'
        ];

        foreach ($booleanFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }
        }

        $order->update($data);
        return response()->json($order);
    }

    /**
     * حذف الطلب
     */
    public function destroy($id, $year): JsonResponse
    {
        Order::where('ID', $id)
             ->where('Year', $year)
             ->delete();

        return response()->json(['message' => 'تم حذف الطلب بنجاح']);
    }
    
    /**
     * 📤 تصدير نتائج البحث
     */
    public function exportSearch(Request $request): JsonResponse
    {
        try {
            $format = $request->get('format', 'csv');
            $query = $this->buildSearchQuery($request->all());
            
            // الحصول على جميع النتائج بدون ترقيم
            $orders = $query->get();
            
            // يمكنك هنا إضافة منطق التصدير الفعلي
            // مثال: استخدام مكتبة Laravel Excel أو PhpSpreadsheet
            
            return response()->json([
                'message' => 'جاري تصدير النتائج',
                'count' => $orders->count(),
                'format' => $format
            ]);
        } catch (\Exception $e) {
            Log::error("خطأ في التصدير: " . $e->getMessage());
            return response()->json(['error' => 'حدث خطأ أثناء التصدير'], 500);
        }
    }
}
