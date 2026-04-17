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

            $lastPage = (int) ceil($total / max($limit, 1));

            return response()->json([
                'data'       => $orders,
                'total'      => $total,
                'page'       => $page,
                'limit'      => $limit,
                'last_page'  => $lastPage,
                'totalPages' => $lastPage, // keep frontend compatibility
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

    // Support both legacy API names and new frontend names.
    $orderId     = $filters['ID'] ?? ($filters['orderId'] ?? null);
    $serial      = $filters['Ser'] ?? ($filters['serialNumber'] ?? null);
    $customer    = $filters['Customer'] ?? ($filters['customer'] ?? null);
    $reference   = $filters['marji3'] ?? ($filters['reference'] ?? null);
    $year        = $filters['Year'] ?? ($filters['year'] ?? null);
    $pattern     = $filters['Pattern'] ?? ($filters['pattern'] ?? null);
    $pattern2    = $filters['Pattern2'] ?? ($filters['pattern2'] ?? null);
    $unitType    = $filters['unit'] ?? ($filters['unitType'] ?? null);
    $code        = $filters['Code'] ?? ($filters['code'] ?? null);

    $dateFrom    = $filters['date_from'] ?? ($filters['dateComeFrom'] ?? null);
    $dateTo      = $filters['date_to'] ?? ($filters['dateComeTo'] ?? null);
    $deliveryFrom = $filters['deliveryDateFrom'] ?? null;
    $deliveryTo   = $filters['deliveryDateTo'] ?? null;

    $demandMin   = $filters['demandMin'] ?? null;
    $demandMax   = $filters['demandMax'] ?? null;
    $priceMin    = $filters['priceMin'] ?? null;
    $priceMax    = $filters['priceMax'] ?? null;

    $printed     = $filters['Printed'] ?? ($filters['isPrinted'] ?? null);
    $billed      = $filters['Billed'] ?? ($filters['isBilled'] ?? null);
    $delivered   = $filters['Reseved'] ?? ($filters['isDelivered'] ?? null);
    $queryText   = $filters['query'] ?? null;

    if ($orderId !== null && $orderId !== '' && is_numeric($orderId)) {
        $query->where('ID', (int) $orderId);
    }

    if ($serial !== null && $serial !== '' && is_numeric($serial)) {
        $query->where('Ser', (int) $serial);
    }

    if (!empty($customer)) {
        $query->where('Customer', 'LIKE', '%' . $customer . '%');
    }

    if (!empty($reference)) {
        $query->where('marji3', 'LIKE', '%' . $reference . '%');
    }

    if (!empty($pattern)) {
        $query->where('Pattern', 'LIKE', '%' . $pattern . '%');
    }

    if (!empty($pattern2)) {
        $query->where('Pattern2', 'LIKE', '%' . $pattern2 . '%');
    }

    if (!empty($unitType)) {
        $query->where('unit', $unitType);
    }

    if (!empty($code)) {
        $query->where('Code', 'LIKE', '%' . $code . '%');
    }

    if (!empty($year)) {
        $query->where('Year', $year);
    }

    if ($printed !== null && $printed !== '') {
        $query->where('Printed', filter_var($printed, FILTER_VALIDATE_BOOLEAN) ? 1 : 0);
    }

    if ($billed !== null && $billed !== '') {
        $query->where('Billed', filter_var($billed, FILTER_VALIDATE_BOOLEAN) ? 1 : 0);
    }

    if ($delivered !== null && $delivered !== '') {
        $query->where('Reseved', filter_var($delivered, FILTER_VALIDATE_BOOLEAN) ? 1 : 0);
    }

    if (!empty($dateFrom)) {
        $query->whereDate('date_come', '>=', $dateFrom);
    }
    if (!empty($dateTo)) {
        $query->whereDate('date_come', '<=', $dateTo);
    }

    if (!empty($deliveryFrom)) {
        $query->whereDate('Apoent_Delv_date', '>=', $deliveryFrom);
    }
    if (!empty($deliveryTo)) {
        $query->whereDate('Apoent_Delv_date', '<=', $deliveryTo);
    }

    if ($demandMin !== null && $demandMin !== '' && is_numeric($demandMin)) {
        $query->where('Demand', '>=', $demandMin);
    }
    if ($demandMax !== null && $demandMax !== '' && is_numeric($demandMax)) {
        $query->where('Demand', '<=', $demandMax);
    }

    if ($priceMin !== null && $priceMin !== '' && is_numeric($priceMin)) {
        $query->where('Price', '>=', $priceMin);
    }
    if ($priceMax !== null && $priceMax !== '' && is_numeric($priceMax)) {
        $query->where('Price', '<=', $priceMax);
    }

    // Generic full-text-ish query across key fields.
    if (!empty($queryText)) {
        $query->where(function ($q) use ($queryText) {
            $q->where('Customer', 'LIKE', '%' . $queryText . '%')
              ->orWhere('Pattern', 'LIKE', '%' . $queryText . '%')
              ->orWhere('Pattern2', 'LIKE', '%' . $queryText . '%')
              ->orWhere('marji3', 'LIKE', '%' . $queryText . '%')
              ->orWhereRaw('CAST([Demand] AS NVARCHAR(50)) LIKE ?', ['%' . $queryText . '%']);

            if (is_numeric($queryText)) {
                $q->orWhere('ID', (int) $queryText)
                  ->orWhere('Ser', (int) $queryText);
            }
        });
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
}

