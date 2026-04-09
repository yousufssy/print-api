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
                'row_id','ID','Year','Ser','Customer','Eng_Name',
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
        $orders = $query->orderByDesc('row_id')
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
            'ID'       => 'required|string|max:50',
            'Year'     => 'required|integer',
            'Customer' => 'required|string|max:255',
        ]);

        $order = Order::create($request->all());

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

        $order->update($request->all());

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
