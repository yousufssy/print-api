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

    // ─────────────────────────────────────────
    // 🔍 بحث متقدم في الطلبات (جديد)
    // ─────────────────────────────────────────
    public function advancedSearch(Request $request): JsonResponse
    {
        try {
            $query = $this->buildSearchQuery($request->all());
            
            // 🔄 الترتيب
            $allowedSorts = ['ID', 'Ser', 'date_come', 'Apoent_Delv_date', 'Demand', 'Price', 'Customer', 'Year'];
            $sortBy = in_array($request->get('sortBy', 'ID'), $allowedSorts) 
                ? $request->sortBy : 'ID';
            $sortOrder = in_array($request->get('sortOrder', 'desc'), ['asc', 'desc']) 
                ? $request->sortOrder : 'desc';
            
            $query->orderBy($sortBy, $sortOrder);
            
            // 📄 Pagination
            $page = max((int) $request->get('page', 1), 1);
            $limit = min((int) $request->get('limit', self::PER_PAGE), 200);
            
            $orders = $query->skip(($page - 1) * $limit)->take($limit)->get();
            $total = $this->buildSearchQuery($request->all())->count(); // ✅ عدّ منفصل للأداء
            
            return response()->json([
                'data'       => $orders,
                'total'      => $total,
                'page'       => $page,
                'limit'      => $limit,
                'totalPages' => ceil($total / $limit),
                'filters'    => $request->only([
                    'query', 'customer', 'orderId', 'year', 'pattern', 'unitType',
                    'isPrinted', 'isBilled', 'isDelivered', 'hasVouchers', 'hasProblems',
                    'sortBy', 'sortOrder'
                ])
            ]);
            
        } catch (\Exception $e) {
            Log::error('Advanced search failed', [
                'error' => $e->getMessage(),
                'filters' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Search error: ' . $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────
    // 🔁 دالة مساعدة: بناء استعلام البحث (لإعادة الاستخدام)
    // ─────────────────────────────────────────
    private function buildSearchQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $query = Order::query()
            ->select([
                'ID','Year','Ser','Customer','Eng_Name',
                'date_come','Apoent_Delv_date','Demand','Clr_qunt',
                'Printed','Billed','Reseved','grnd_qunt','Pattern','Pattern2',
                'unit','Price','Code_M','marji3','note_ord'
            ]);

        // 🔍 بحث نصي عام في حقول متعددة
        if (!empty($filters['query'])) {
            $q = trim($filters['query']);
            $query->where(function($sub) use ($q) {
                $sub->where('Customer', 'LIKE', "%{$q}%")
                    ->orWhere('Pattern', 'LIKE', "%{$q}%")
                    ->orWhere('Pattern2', 'LIKE', "%{$q}%")
                    ->orWhere('marji3', 'LIKE', "%{$q}%")
                    ->orWhere('note_ord', 'LIKE', "%{$q}%")
                    ->orWhere('ID', 'LIKE', "%{$q}%");
            });
        }

        // 📋 فلاتر البيانات الأساسية
        if (!empty($filters['orderId'])) {
            $query->where('ID', $filters['orderId']);
        }
        if (!empty($filters['serialNumber'])) {
            $query->where('Ser', $filters['serialNumber']);
        }
        if (!empty($filters['customer'])) {
            $query->where('Customer', 'LIKE', "%{$filters['customer']}%");
        }
        if (!empty($filters['reference'])) {
            $query->where('marji3', 'LIKE', "%{$filters['reference']}%");
        }
        if (!empty($filters['year'])) {
            $query->where('Year', $filters['year']);
        }

        // 📅 فلاتر التواريخ
        if (!empty($filters['dateComeFrom'])) {
            $query->where('date_come', '>=', $filters['dateComeFrom']);
        }
        if (!empty($filters['dateComeTo'])) {
            $query->where('date_come', '<=', $filters['dateComeTo']);
        }
        if (!empty($filters['deliveryDateFrom'])) {
            $query->where('Apoent_Delv_date', '>=', $filters['deliveryDateFrom']);
        }
        if (!empty($filters['deliveryDateTo'])) {
            $query->where('Apoent_Delv_date', '<=', $filters['deliveryDateTo']);
        }

        // 🎨 مواصفات المطبوعة
        if (!empty($filters['pattern'])) {
            $query->where(function($q) use ($filters) {
                $q->where('Pattern', 'LIKE', "%{$filters['pattern']}%")
                  ->orWhere('Pattern2', 'LIKE', "%{$filters['pattern']}%");
            });
        }
        if (!empty($filters['unitType'])) {
            $query->where('unit', $filters['unitType']);
        }
        if (!empty($filters['code'])) {
            $query->where('Code_M', 'LIKE', "%{$filters['code']}%");
        }

        // 📊 فلاتر الكميات والأسعار
        if (!empty($filters['demandMin'])) {
            $query->where('Demand', '>=', $filters['demandMin']);
        }
        if (!empty($filters['demandMax'])) {
            $query->where('Demand', '<=', $filters['demandMax']);
        }
        if (!empty($filters['priceMin'])) {
            $query->where('Price', '>=', $filters['priceMin']);
        }
        if (!empty($filters['priceMax'])) {
            $query->where('Price', '<=', $filters['priceMax']);
        }

        // ✅ فلاتر الحالة (boolean) - تحويل للقيم 1/0
        if (isset($filters['isPrinted'])) {
            $val = filter_var($filters['isPrinted'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            $query->where('Printed', $val);
        }
        if (isset($filters['isBilled'])) {
            $val = filter_var($filters['isBilled'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            $query->where('Billed', $val);
        }
        if (isset($filters['isDelivered'])) {
            $val = filter_var($filters['isDelivered'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            $query->where('Reseved', $val);
        }

        // 🔗 فلاتر العلاقات: وجود سجلات مرتبطة
        // ⚠️ ملاحظة: تأكد من تعريف العلاقات في Model Order
        if (isset($filters['hasVouchers'])) {
            $has = filter_var($filters['hasVouchers'], FILTER_VALIDATE_BOOLEAN);
            $query->whereHas('vouchers', function($q) {
                $q->select('ID'); // EXISTS check
            }, $has ? '>=' : '=', 1);
        }
        if (isset($filters['hasProblems'])) {
            $has = filter_var($filters['hasProblems'], FILTER_VALIDATE_BOOLEAN);
            $query->whereHas('problems', function($q) {
                $q->select('_ID');
            }, $has ? '>=' : '=', 1);
        }
        if (isset($filters['hasCartons'])) {
            $has = filter_var($filters['hasCartons'], FILTER_VALIDATE_BOOLEAN);
            $query->whereHas('cartons', function($q) {
                $q->select('ID1');
            }, $has ? '>=' : '=', 1);
        }

        // ⚙️ فلاتر العمليات والمواد (عبر العلاقات)
        if (!empty($filters['operationType'])) {
            $query->whereHas('operations', function($q) use ($filters) {
                $q->where('Action', 'LIKE', "%{$filters['operationType']}%");
            });
        }
        if (!empty($filters['machineName'])) {
            $query->whereHas('operations', function($q) use ($filters) {
                $q->where('Machin', 'LIKE', "%{$filters['machineName']}%");
            });
        }
        if (!empty($filters['materialSupplier'])) {
            $query->whereHas('cartons', function($q) use ($filters) {
                $q->where('Supplier1', 'LIKE', "%{$filters['materialSupplier']}%");
            });
        }

        return $query;
    }

    // ─────────────────────────────────────────
    // 📤 تصدير نتائج البحث (CSV)
    // ─────────────────────────────────────────
    public function exportSearch(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filters = $request->all();
        unset($filters['page'], $filters['limit']); // لا نحتاج ترقيم في التصدير
        
        $query = $this->buildSearchQuery($filters);
        $orders = $query->limit(10000)->get(); // حد أقصى للتصدير
        
        $filename = "orders_export_" . date('Y-m-d_H-i') . ".csv";
        
        return response()->streamDownload(function() use ($orders) {
            $handle = fopen('php://output', 'w');
            
            // ✅ UTF-8 BOM لدعم العربية في Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // العناوين
            fputcsv($handle, [
                'رقم الطلب', 'التسلسل', 'الزبون', 'النموذج', 'الوصف', 
                'النوع', 'الكمية', 'السعر', 'تاريخ الورود', 'موعد التسليم', 'الحالة'
            ], ',', '"', '\\');
            
            // البيانات
            foreach ($orders as $o) {
                $status = implode('/', array_filter([
                    $o->Printed ? 'مطبوع' : null,
                    $o->Billed ? 'مفوتر' : null,
                    $o->Reseved ? 'مُسلَّم' : null
                ]));
                
                fputcsv($handle, [
                    $o->ID,
                    $o->Ser,
                    $o->Customer,
                    $o->Pattern,
                    $o->Pattern2,
                    $o->unit,
                    $o->Demand,
                    $o->Price,
                    $o->date_come,
                    $o->Apoent_Delv_date,
                    $status ?: 'جديد'
                ], ',', '"', '\\');
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    // ─────────────────────────────────────────
    // عرض الطلبات مع تحسين الأداء (الطريقة الأصلية)
    // ─────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $year   = (int) $request->get('year', date('Y'));
        $q      = $request->get('q', '');
        $status = $request->get('status', '');

        $query = Order::query()
            ->select([
                'ID','Year','Ser','Customer','Eng_Name',
                'date_come','Apoent_Delv_date','Demand','Clr_qunt',
                'Printed','Billed','Reseved','grnd_qunt','Pattern','Machin_Print'
            ])
            ->where('Year', $year);

        if ($q) {
            $query->where(function ($qBuilder) use ($q) {
                $qBuilder->where('ID', 'like', "%$q%")
                         ->orWhere('Customer', 'like', "%$q%");
            });
        }

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

        $page = (int) $request->get('page', 1);
        $orders = $query->orderByDesc('ID')
                        ->skip(($page - 1) * self::PER_PAGE)
                        ->take(self::PER_PAGE)
                        ->get();

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
