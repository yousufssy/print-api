<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    private const PER_PAGE = 50;

    /**
     * عرض قائمة الطلبات
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $year = $request->get('year', date('Y'));
            $q = $request->get('q');

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
            
        } catch (\Exception $e) {
            Log::error('خطأ في index: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * إنشاء طلب جديد
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->all();

            // تعيين السنة إذا لم تكن موجودة
            if (!isset($data['Year'])) {
                $data['Year'] = date('Y');
            }

            // معالجة الحقول Boolean
            $booleanFields = [
                'Printed', 'Billed', 'DubelM', 'varnich', 'uv_Spot', 'uv',
                'seluvan_lum', 'seluvan_mat', 'Tay', 'Tad3em', 'harary',
                'rolling', 'rollingBack', 'Reseved'
            ];

            foreach ($booleanFields as $field) {
                if (isset($data[$field])) {
                    $data[$field] = $data[$field] ? 1 : 0;
                }
            }

            // إنشاء الطلب
            $order = Order::create($data);
            
            return response()->json($order, 201);
            
        } catch (\Exception $e) {
            Log::error('خطأ في store: ' . $e->getMessage());
            return response()->json([
                'error' => 'فشل في إنشاء الطلب',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض طلب واحد
     */
    public function show($id, $year): JsonResponse
    {
        try {
            $order = Order::where('ID', $id)
                          ->where('Year', $year)
                          ->first();

            if (!$order) {
                return response()->json([
                    'error' => 'الطلب غير موجود'
                ], 404);
            }

            // تحميل السندات إذا وجدت
            try {
                $order->load('vouchers');
            } catch (\Exception $e) {
                // تجاهل خطأ تحميل السندات
            }
            
            return response()->json($order);
            
        } catch (\Exception $e) {
            Log::error('خطأ في show: ' . $e->getMessage());
            return response()->json([
                'error' => 'فشل في عرض الطلب',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث الطلب
     */
    public function update(Request $request, $id, $year): JsonResponse
    {
        try {
            // البحث عن الطلب
            $order = Order::where('ID', $id)
                          ->where('Year', $year)
                          ->first();

            if (!$order) {
                return response()->json([
                    'error' => 'الطلب غير موجود',
                    'details' => "ID: {$id}, Year: {$year}"
                ], 404);
            }

            $data = $request->all();

            // إزالة الحقول التي لا يجب تعديلها
            unset($data['ID']);
            unset($data['Year']);

            // معالجة الحقول Boolean
            $booleanFields = [
                'Printed', 'Billed', 'DubelM', 'varnich', 'uv_Spot', 'uv',
                'seluvan_lum', 'seluvan_mat', 'Tay', 'Tad3em', 'harary',
                'rolling', 'rollingBack', 'Reseved'
            ];

            foreach ($booleanFields as $field) {
                if (isset($data[$field])) {
                    $data[$field] = $data[$field] ? 1 : 0;
                }
            }

            // تحديث البيانات
            $order->update($data);
            
            // إعادة تحميل البيانات المحدثة
            $order = Order::where('ID', $id)
                          ->where('Year', $year)
                          ->first();
            
            return response()->json($order);
            
        } catch (\Exception $e) {
            Log::error('خطأ في update: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'فشل في تحديث الطلب',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف الطلب
     */
    public function destroy($id, $year): JsonResponse
    {
        try {
            $order = Order::where('ID', $id)
                         ->where('Year', $year)
                         ->first();

            if (!$order) {
                return response()->json([
                    'error' => 'الطلب غير موجود'
                ], 404);
            }

            $order->delete();

            return response()->json([
                'message' => 'تم حذف الطلب بنجاح',
                'deleted' => [
                    'ID' => $id,
                    'Year' => $year
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('خطأ في destroy: ' . $e->getMessage());
            return response()->json([
                'error' => 'فشل في حذف الطلب',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * البحث المتقدم
     */
    public function advancedSearch(Request $request): JsonResponse
    {
        try {
            $query = $this->buildSearchQuery($request->all());

            // الترتيب
            $allowedSorts = ['ID', 'Ser', 'date_come', 'Apoent_Delv_date', 'Demand', 'Price', 'Customer', 'Year'];
            $sortBy = $request->get('sortBy', 'ID');
            $sortOrder = $request->get('sortOrder', 'desc');

            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // الترقيم
            $page = max((int) $request->get('page', 1), 1);
            $limit = min((int) $request->get('limit', self::PER_PAGE), 200);

            $total = $query->count();
            $orders = $query->skip(($page - 1) * $limit)->take($limit)->get();

            $lastPage = (int) ceil($total / max($limit, 1));

            return response()->json([
                'data' => $orders,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'last_page' => $lastPage,
                'totalPages' => $lastPage,
            ]);
            
        } catch (\Exception $e) {
            Log::error('خطأ في advancedSearch: ' . $e->getMessage());
            return response()->json([
                'error' => 'حدث خطأ أثناء البحث',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * بناء استعلام البحث
     */
    protected function buildSearchQuery(array $filters)
    {
        $query = Order::query();

        // ID
        if (!empty($filters['ID']) && is_numeric($filters['ID'])) {
            $query->where('ID', (int) $filters['ID']);
        }

        // Serial
        if (!empty($filters['Ser']) && is_numeric($filters['Ser'])) {
            $query->where('Ser', (int) $filters['Ser']);
        }

        // Customer
        if (!empty($filters['Customer'])) {
            $query->where('Customer', 'LIKE', '%' . $filters['Customer'] . '%');
        }

        // Year
        if (!empty($filters['Year'])) {
            $query->where('Year', $filters['Year']);
        }

        // Pattern
        if (!empty($filters['Pattern'])) {
            $query->where('Pattern', 'LIKE', '%' . $filters['Pattern'] . '%');
        }

        // Date Range
        if (!empty($filters['date_from'])) {
            $query->whereDate('date_come', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('date_come', '<=', $filters['date_to']);
        }

        // Printed
        if (isset($filters['Printed']) && $filters['Printed'] !== '') {
            $query->where('Printed', $filters['Printed'] ? 1 : 0);
        }

        // Billed
        if (isset($filters['Billed']) && $filters['Billed'] !== '') {
            $query->where('Billed', $filters['Billed'] ? 1 : 0);
        }

        // Delivered
        if (isset($filters['Reseved']) && $filters['Reseved'] !== '') {
            $query->where('Reseved', $filters['Reseved'] ? 1 : 0);
        }

        return $query;
    }
}
