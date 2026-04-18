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
    // 🔍 البحث المتقدم
    // ─────────────────────────────────────────
    public function advancedSearch(Request $request): JsonResponse
    {
        try {
            $query = $this->buildSearchQuery($request->all());

            $allowedSorts = ['ID','Ser','date_come','Apoent_Delv_date','Demand','Price','Customer','Year'];
            $sortBy    = in_array($request->get('sortBy','ID'), $allowedSorts) ? $request->get('sortBy','ID') : 'ID';
            $sortOrder = in_array($request->get('sortOrder','desc'), ['asc','desc']) ? $request->get('sortOrder','desc') : 'desc';

            $query->orderBy($sortBy, $sortOrder);

            $page  = max((int) $request->get('page', 1), 1);
            $limit = min((int) $request->get('limit', self::PER_PAGE), 200);

            $total  = (clone $query)->count();
            $orders = $query->skip(($page - 1) * $limit)->take($limit)->get();

            return response()->json([
                'data'       => $orders,
                'total'      => $total,
                'page'       => $page,
                'limit'      => $limit,
                'totalPages' => $limit > 0 ? (int) ceil($total / $limit) : 1,
            ]);

        } catch (\Exception $e) {
            Log::error('Advanced search failed', ['error' => $e->getMessage(), 'filters' => $request->all()]);
            return response()->json(['message' => 'Search error: ' . $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────
    // 🔁 بناء استعلام البحث
    // ─────────────────────────────────────────
    private function buildSearchQuery(array $f): \Illuminate\Database\Eloquent\Builder
    {
        $query = Order::query()->select([
            'ID','Year','Ser','Customer','Eng_Name',
            'date_come','Apoent_Delv_date','Demand','Clr_qunt',
            'Printed','Billed','Reseved','grnd_qunt',
            'Pattern','Pattern2','unit','Price','Code_M','marji3','note_ord'
        ]);

        // 🔍 بحث نصي عام
        if (!empty($f['query'])) {
            $q = trim($f['query']);
            $query->where(function($sub) use ($q) {
                $sub->where('Customer','LIKE',"%{$q}%")
                    ->orWhere('Pattern','LIKE',"%{$q}%")
                    ->orWhere('Pattern2','LIKE',"%{$q}%")
                    ->orWhere('marji3','LIKE',"%{$q}%")
                    ->orWhere('note_ord','LIKE',"%{$q}%")
                    ->orWhere('ID','LIKE',"%{$q}%");
            });
        }

        // 📋 حقول أساسية
        if (!empty($f['orderId']))      $query->where('ID', $f['orderId']);
        if (!empty($f['serialNumber'])) $query->where('Ser', $f['serialNumber']);
        if (!empty($f['customer']))     $query->where('Customer','LIKE',"%{$f['customer']}%");
        if (!empty($f['reference']))    $query->where('marji3','LIKE',"%{$f['reference']}%");
        if (!empty($f['year']))         $query->where('Year', $f['year']);

        // 📅 تواريخ
        if (!empty($f['dateComeFrom']))     $query->where('date_come','>=', $f['dateComeFrom']);
        if (!empty($f['dateComeTo']))       $query->where('date_come','<=', $f['dateComeTo']);
        if (!empty($f['deliveryDateFrom'])) $query->where('Apoent_Delv_date','>=', $f['deliveryDateFrom']);
        if (!empty($f['deliveryDateTo']))   $query->where('Apoent_Delv_date','<=', $f['deliveryDateTo']);

        // 🎨 مواصفات المطبوعة
        if (!empty($f['pattern'])) {
            $query->where(function($q) use ($f) {
                $q->where('Pattern','LIKE',"%{$f['pattern']}%")
                  ->orWhere('Pattern2','LIKE',"%{$f['pattern']}%");
            });
        }
        if (!empty($f['unitType'])) $query->where('unit', $f['unitType']);
        if (!empty($f['code']))     $query->where('Code_M','LIKE',"%{$f['code']}%");

        // 📊 كميات وأسعار
        if (!empty($f['demandMin'])) $query->where('Demand','>=', $f['demandMin']);
        if (!empty($f['demandMax'])) $query->where('Demand','<=', $f['demandMax']);
        if (!empty($f['priceMin']))  $query->where('Price','>=', $f['priceMin']);
        if (!empty($f['priceMax']))  $query->where('Price','<=', $f['priceMax']);

        // ✅ حالة الطلب
        if (isset($f['isPrinted']) && $f['isPrinted'] !== '')
            $query->where('Printed', filter_var($f['isPrinted'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0);
        if (isset($f['isBilled']) && $f['isBilled'] !== '')
            $query->where('Billed', filter_var($f['isBilled'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0);
        if (isset($f['isDelivered']) && $f['isDelivered'] !== '')
            $query->where('Reseved', filter_var($f['isDelivered'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0);

        // 🔗 وجود سجلات مرتبطة — بـ subquery مباشر بدل whereHas
        if (isset($f['hasVouchers']) && $f['hasVouchers'] !== '') {
            $has = filter_var($f['hasVouchers'], FILTER_VALIDATE_BOOLEAN);
            if ($has) {
                $query->whereExists(fn($q) => $q->select(DB::raw(1))->from('vouchers')->whereColumn('vouchers.ID','MasterW.ID')->whereColumn('vouchers.Year','MasterW.Year'));
            } else {
                $query->whereNotExists(fn($q) => $q->select(DB::raw(1))->from('vouchers')->whereColumn('vouchers.ID','MasterW.ID')->whereColumn('vouchers.Year','MasterW.Year'));
            }
        }

        if (isset($f['hasProblems']) && $f['hasProblems'] !== '') {
            $has = filter_var($f['hasProblems'], FILTER_VALIDATE_BOOLEAN);
            if ($has) {
                $query->whereExists(fn($q) => $q->select(DB::raw(1))->from('problems')->whereColumn('problems.ID','MasterW.ID')->whereColumn('problems.Year','MasterW.Year'));
            } else {
                $query->whereNotExists(fn($q) => $q->select(DB::raw(1))->from('problems')->whereColumn('problems.ID','MasterW.ID')->whereColumn('problems.Year','MasterW.Year'));
            }
        }

        if (isset($f['hasCartons']) && $f['hasCartons'] !== '') {
            $has = filter_var($f['hasCartons'], FILTER_VALIDATE_BOOLEAN);
            if ($has) {
                $query->whereExists(fn($q) => $q->select(DB::raw(1))->from('Carton')->whereColumn('Carton.ID','MasterW.ID')->whereColumn('Carton.year','MasterW.Year'));
            } else {
                $query->whereNotExists(fn($q) => $q->select(DB::raw(1))->from('Carton')->whereColumn('Carton.ID','MasterW.ID')->whereColumn('Carton.year','MasterW.Year'));
            }
        }

        // ⚙️ العمليات والمواد
        if (!empty($f['operationType'])) {
            $query->whereExists(fn($q) => $q->select(DB::raw(1))->from('actions')
                ->whereColumn('actions.ID','MasterW.ID')
                ->whereColumn('actions.Year','MasterW.Year')
                ->where('actions.Action','LIKE',"%{$f['operationType']}%"));
        }
        if (!empty($f['machineName'])) {
            $query->whereExists(fn($q) => $q->select(DB::raw(1))->from('actions')
                ->whereColumn('actions.ID','MasterW.ID')
                ->whereColumn('actions.Year','MasterW.Year')
                ->where('actions.Machin','LIKE',"%{$f['machineName']}%"));
        }
        if (!empty($f['materialSupplier'])) {
            $query->whereExists(fn($q) => $q->select(DB::raw(1))->from('Carton')
                ->whereColumn('Carton.ID','MasterW.ID')
                ->whereColumn('Carton.year','MasterW.Year')
                ->where('Carton.Supplier1','LIKE',"%{$f['materialSupplier']}%"));
        }

        // ✅ فلتر التصدير — يبحث بـ LIKE في حقل Form
        $isExport = $filters['isExport'] ?? null;
        if (!empty($isExport)) {
            $query->where('Form', 'LIKE', '%' . $isExport . '%');
        }

        return $query;
    }

    // ─────────────────────────────────────────
    // 📤 تصدير نتائج البحث (CSV)
    // ─────────────────────────────────────────
    public function exportSearch(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filters = $request->all();
        unset($filters['page'], $filters['limit']);

        $orders = $this->buildSearchQuery($filters)->limit(10000)->get();
        $filename = "orders_export_" . date('Y-m-d_H-i') . ".csv";

        return response()->streamDownload(function() use ($orders) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['رقم الطلب','التسلسل','الزبون','النموذج','الوصف','النوع','الكمية','السعر','تاريخ الورود','موعد التسليم','الحالة']);
            foreach ($orders as $o) {
                $status = implode('/', array_filter([$o->Printed ? 'مطبوع' : null, $o->Billed ? 'مفوتر' : null, $o->Reseved ? 'مُسلَّم' : null]));
                fputcsv($handle, [$o->ID,$o->Ser,$o->Customer,$o->Pattern,$o->Pattern2,$o->unit,$o->Demand,$o->Price,$o->date_come,$o->Apoent_Delv_date,$status ?: 'جديد']);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ─────────────────────────────────────────
    // عرض الطلبات
    // ─────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $year   = (int) $request->get('year', date('Y'));
        $q      = $request->get('q', '');
        $status = $request->get('status', '');

        $query = Order::query()
            ->select(['ID','Year','Ser','Customer','Eng_Name','date_come','Apoent_Delv_date','Demand','Clr_qunt','Printed','Billed','Reseved','grnd_qunt','Pattern','Machin_Print'])
            ->where('Year', $year);

        if ($q) {
            $query->where(function($qb) use ($q) {
                $qb->where('ID','like',"%$q%")->orWhere('Customer','like',"%$q%");
            });
        }

        if ($status === 'printed')   $query->where('Printed', true);
        elseif ($status === 'billed')  $query->where('Billed', true);
        elseif ($status === 'delivered') $query->whereNotNull('Reseved')->where('Reseved','!=','')->where('Reseved','!=','0');
        elseif ($status === 'new') $query->where(function($qb) { $qb->whereNull('Printed')->orWhere('Printed', false); });

        $page   = (int) $request->get('page', 1);
        $orders = $query->orderByDesc('ID')->skip(($page - 1) * self::PER_PAGE)->take(self::PER_PAGE)->get();
        $total  = (clone $query)->count();

        return response()->json(['data' => $orders, 'total' => $total, 'page' => $page, 'last_page' => ceil($total / self::PER_PAGE)]);
    }

    // ─────────────────────────────────────────
    // إنشاء طلب
    // ─────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['ID' => 'required', 'Year' => 'required|integer', 'Customer' => 'nullable|string|max:255']);
        $data = $request->all();

        $booleanFields = ['Printed','Billed','DubelM','varnich','uv_Spot','uv','seluvan_lum','seluvan_mat','Tay','Tad3em','harary','rolling','rollingBack','Reseved','tabkha','bals'];
        foreach ($booleanFields as $field) {
            if (isset($data[$field])) $data[$field] = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }

        $order = Order::create($data);
        return response()->json($order, 201);
    }

    // ─────────────────────────────────────────
    // عرض طلب
    // ─────────────────────────────────────────
    public function show($id, $year): JsonResponse
    {
        $order = Order::where('ID', $id)->where('Year', $year)->firstOrFail();
        $order->load('vouchers');
        return response()->json($order);
    }

    // ─────────────────────────────────────────
    // تحديث طلب
    // ─────────────────────────────────────────
    public function update(Request $request, $id, $year): JsonResponse
    {
        $order = Order::where('ID', $id)->where('Year', $year)->firstOrFail();
        $data  = $request->all();

        $booleanFields = ['Printed','Billed','DubelM','varnich','uv_Spot','uv','seluvan_lum','seluvan_mat','Tay','Tad3em','harary','rolling','rollingBack','Reseved'];
        foreach ($booleanFields as $field) {
            if (isset($data[$field])) $data[$field] = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }

        $order->update($data);
        return response()->json($order);
    }

    // ─────────────────────────────────────────
    // حذف طلب
    // ─────────────────────────────────────────
    public function destroy($id, $year): JsonResponse
    {
        Order::where('ID', $id)->where('Year', $year)->delete();
        return response()->json(['message' => 'Deleted successfully.']);
    }
}
