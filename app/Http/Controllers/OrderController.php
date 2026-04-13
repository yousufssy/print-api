<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    private const PER_PAGE = 50;

    // ─────────────────────────────────────────
    // عرض الطلبات مع تحسين الأداء
    // ─────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $year   = (int) $request->get('year', date('Y'));
        $q      = $request->get('q', '');
        $status = $request->get('status', '');

        // استخدام query builder مباشرة مع اختيار الأعمدة الضرورية فقط
        $query = Order::query()
            ->select([
                'ID','Year','Ser','Customer','Eng_Name',
                'date_come','Apoent_Delv_date','Demand','Clr_qunt',
                'Printed','Billed','Reseved','grnd_qunt','Pattern','Machin_Print'
            ])
            ->where('Year', $year);

        // البحث
        if ($q) {
            $query->where(function ($qBuilder) use ($q) {
                $qBuilder->where('ID', 'like', "%$q%")
                         ->orWhere('Customer', 'like', "%$q%");
            });
        }

        // الفلاتر
        if ($status === 'printed') {
            $query->where('Printed', true);
        } elseif ($status === 'billed') {
            $query->where('Billed', true);
        } elseif ($status === 'delivered') {
            $query->whereNotNull('Reseved')
                  ->where('Reseved', '!=', '')
                  ->where('Reseved', '!=', '0');
        } elseif ($status === 'new') {
            $query->where(function ($qBuilder) {
                $qBuilder->whereNull('Printed')
                         ->orWhere('Printed', false);
            });
        }

        // استخدام pagination + count منفصل لتحسين الأداء مع البيانات الكبيرة
        $page = (int) $request->get('page', 1);
        $orders = $query->orderByDesc('ID')
                        ->skip(($page - 1) * self::PER_PAGE)
                        ->take(self::PER_PAGE)
                        ->get();

        // إجمالي عدد الصفوف (استخدم cache إذا لاحظت بطء)
        $total = $query->count();

        return response()->json([
            'data'      => $orders,
            'total'     => $total,
            'page'      => $page,
            'last_page' => ceil($total / self::PER_PAGE),
        ]);
    }

    // ─────────────────────────────────────────
    // إنشاء طلب
    // ─────────────────────────────────────────
    public function store(Request $request): JsonResponse
{
    $validated = $request->validate([
        'ID'       => 'required',
        'Year'     => 'required|integer',
        'Customer' => 'nullable|string|max:255',
    ]);

    $data = $request->all();

    // حوّل "True"/"False" لـ 1/0
    $booleanFields = [
        'Printed', 'Billed', 'DubelM', 'varnich', 'uv_Spot', 'uv',
        'seluvan_lum', 'seluvan_mat', 'Tay', 'Tad3em', 'harary',
        'rolling', 'rollingBack', 'Reseved', 'tabkha', 'bals'
    ];

    foreach ($booleanFields as $field) {
        if (isset($data[$field])) {
            $data[$field] = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }
    }

    $order = Order::create($data);

    return response()->json($order, 201);
}

    // ─────────────────────────────────────────
    // عرض طلب
    // ─────────────────────────────────────────
    public function show($id, $year): JsonResponse
        {
            $order = Order::where('ID', $id)
                          ->where('Year', $year)
                          ->firstOrFail();
        
            $order->load('vouchers');
        
            return response()->json($order);
        }

    // ─────────────────────────────────────────
    // تحديث طلب
    // ─────────────────────────────────────────
 public function update(Request $request, $id, $year): JsonResponse
{
    $order = Order::where('ID', $id)
                  ->where('Year', $year)
                  ->firstOrFail();

    $data = $request->all();

    // حوّل "True"/"False" لـ 1/0
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

    // ─────────────────────────────────────────
    // حذف طلب
    // ─────────────────────────────────────────
    public function destroy($id, $year): JsonResponse
    {
        Order::where('ID', $id)
             ->where('Year', $year)
             ->delete();

        return response()->json(['message' => 'Deleted successfully.']);
    }
}
