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

            $allowedSorts = ['ID', 'Ser', 'date_come', 'Apoent_Delv_date', 'Demand', 'Price', 'Customer', 'Year'];
            $sortBy    = in_array($request->get('sortBy', 'ID'), $allowedSorts) ? $request->get('sortBy', 'ID') : 'ID';
            $sortOrder = in_array($request->get('sortOrder', 'desc'), ['asc', 'desc']) ? $request->get('sortOrder', 'desc') : 'desc';

            $query->orderBy($sortBy, $sortOrder);

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
                'totalPages' => $lastPage,
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

        $orderId      = $filters['ID']               ?? ($filters['orderId']       ?? null);
        $serial       = $filters['Ser']              ?? ($filters['serialNumber']  ?? null);
        $customer     = $filters['Customer']         ?? ($filters['customer']      ?? null);
        $reference    = $filters['marji3']           ?? ($filters['reference']     ?? null);
        $year         = $filters['Year']             ?? ($filters['year']          ?? null);
        $pattern      = $filters['Pattern']          ?? ($filters['pattern']       ?? null);
        $pattern2     = $filters['Pattern2']         ?? ($filters['pattern2']      ?? null);
        $unitType     = $filters['unit']             ?? ($filters['unitType']      ?? null);
        $code         = $filters['Code']             ?? ($filters['code']          ?? null);

        $dateFrom     = $filters['date_from']        ?? ($filters['dateComeFrom']  ?? null);
        $dateTo       = $filters['date_to']          ?? ($filters['dateComeTo']    ?? null);
        $deliveryFrom = $filters['deliveryDateFrom'] ?? null;
        $deliveryTo   = $filters['deliveryDateTo']   ?? null;

        $demandMin    = $filters['demandMin']        ?? null;
        $demandMax    = $filters['demandMax']        ?? null;
        $priceMin     = $filters['priceMin']         ?? null;
        $priceMax     = $filters['priceMax']         ?? null;

        $printed      = $filters['Printed']          ?? ($filters['isPrinted']     ?? null);
        $billed       = $filters['Billed']           ?? ($filters['isBilled']      ?? null);
        $delivered    = $filters['Reseved']          ?? ($filters['isDelivered']   ?? null);
        $queryText    = $filters['query']            ?? null;
        $formto       = $filters['Form']             ?? ($filters['code']          ?? null);

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
        if (!empty($formto)) {
            $query->where('Form', 'LIKE', '%' . $formto . '%');
        }
        if (!empty($queryText)) {
            $query->where(function ($q) use ($queryText) {
                $q->where('Customer', 'LIKE', '%' . $queryText . '%')
                  ->orWhere('Pattern',  'LIKE', '%' . $queryText . '%')
                  ->orWhere('Pattern2', 'LIKE', '%' . $queryText . '%')
                  ->orWhere('marji3',   'LIKE', '%' . $queryText . '%')
                  ->orWhereRaw('CAST([Demand] AS NVARCHAR(50)) LIKE ?', ['%' . $queryText . '%']);

                if (is_numeric($queryText)) {
                    $q->orWhere('ID',  (int) $queryText)
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
    $data      = $request->all();
    $tableName = (new Order())->getTable();
    $year      = $data['Year'] ?? date('Y');

    // ✅ إذا كان ID فارغاً أو غير موجود — احسب max+1
    if (empty($data['ID'])) {
        $maxId      = DB::table($tableName)->max('ID') ?? 0;
        $data['ID'] = $maxId + 1;
    } else {
        // ✅ المستخدم أدخل ID — تحقق أن ID + Year غير موجودَين معاً
        $exists = DB::table($tableName)
                    ->where('ID', (int) $data['ID'])
                    ->where('Year', $year)
                    ->exists();

        if ($exists) {
            return response()->json([
                'error' => "الرقم {$data['ID']} مستخدم مسبقاً في سنة {$year}. الرجاء اختيار رقم مختلف.",
                'code'  => 'ID_YEAR_DUPLICATE',
            ], 409);
        }
    }

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

    // ✅ تصفية الحقول بـ fillable
    $fillable = (new Order())->getFillable();
    $data     = array_intersect_key($data, array_flip($fillable));

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
     *
     * ✅ يعتمد على ID و Year معاً عند الحفظ
     * ✅ يستخدم getFillable() لتصفية الحقول المسموح بها فقط
     *    (يتجنب خطأ Unknown column تلقائياً بدون قائمة يدوية)
     */
    public function update(Request $request, $id, $year): JsonResponse
    {
        // تحقق من وجود السجل بـ ID و Year معاً
        $order = Order::where('ID', $id)
                      ->where('Year', $year)
                      ->firstOrFail();

        $data = $request->all();

        // ✅ إبقاء فقط الحقول الموجودة في $fillable بالـ Model
        //    هذا يحذف تلقائياً: Qunt_Ac, vouchers, tabkha, bals, cut1, cut2...
        //    وأي حقل غير موجود في الجدول
        $fillable = $order->getFillable();
        $data = array_intersect_key($data, array_flip($fillable));

        // معالجة حقول البوليان
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

        // ✅ التحديث بـ WHERE ID و Year معاً باستخدام اسم الجدول من الـ Model
        DB::table($order->getTable())
            ->where('ID', $id)
            ->where('Year', $year)
            ->update($data);

        $order->refresh();
        return response()->json($order);
    }

    /**
     * نسخ طلب إلى سنة جديدة مع كل الجداول المرتبطة
     *
     * POST /api/orders/{id}/{year}/copy
     * Body (اختياري): { "Year": 2026 }
     *
     * ✅ marji3 = {ID الأصلي}/{سنة النسخ}
     * ✅ OldID / OldYear يحفظان مرجع المصدر
     * ✅ تُصفَّر: Printed, Billed, Reseved, delev_date
     * ✅ تُنسخ الجداول المرتبطة: vouchers, actions, Carton, materials, problems
     */
    public function copy(Request $request, $id, $year): JsonResponse
    {
        DB::beginTransaction();
        try {
            $original = Order::where('ID', $id)->where('Year', $year)->firstOrFail();

            $tableName = $original->getTable();
            $fillable  = $original->getFillable();
            $copyYear  = $request->input('Year', date('Y'));
            $newId     = (DB::table($tableName)->max('ID') ?? 0) + 1;

            $rawData = DB::table($tableName)->where('ID', $id)->where('Year', $year)->first();
            $newData = array_intersect_key((array) $rawData, array_flip($fillable));

            $newData['ID']         = $newId;
            $newData['Year']       = $copyYear;
            $newData['marji3']     = "{$id}/{$copyYear}";
            $newData['OldID']      = $id;
            $newData['OldYear']    = $year;
            $newData['Printed']    = 0;
            $newData['Billed']     = 0;
            $newData['Reseved']    = 0;
            $newData['delev_date'] = null;

            DB::table($tableName)->insert($newData);

            // نسخ الجداول المرتبطة عبر الـ helper المشترك
            $this->copyRelatedTables((int) $id, $year, $newId, $copyYear);

            DB::commit();

            $newOrder = Order::where('ID', $newId)->where('Year', $copyYear)->first();

            return response()->json([
                'message'     => 'تم نسخ الطلب وجميع البيانات المرتبطة بنجاح',
                'new_order'   => $newOrder,
                'copied_from' => ['ID' => (int) $id, 'Year' => $year],
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'الطلب الأصلي غير موجود'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("خطأ في نسخ الطلب: " . $e->getMessage());
            return response()->json(['error' => 'حدث خطأ: ' . $e->getMessage()], 500);
        }
    }

    /**
     * ─────────────────────────────────────────────────────────────────────
     * تدوير الطلبات المتبقية (Reseved=0, Billed=0) من سنة إلى سنة جديدة
     *
     * POST /api/orders/rollover
     * Body: { "from_year": 2025, "to_year": 2026 }
     *
     * ✅ يدوّر فقط الطلبات غير المسلَّمة وغير المحاسَبة
     * ✅ ينسخ كل الجداول المرتبطة لكل طلب
     * ✅ marji3 = {ID الأصلي}/{سنة الجديدة}
     * ✅ كل العملية في transaction واحدة
     * ─────────────────────────────────────────────────────────────────────
     */
    public function rollover(Request $request): JsonResponse
    {
        $fromYear = $request->input('from_year');
        $toYear   = $request->input('to_year');

        if (!$fromYear || !$toYear) {
            return response()->json(['error' => 'يجب إرسال from_year و to_year'], 422);
        }
        if ((int) $toYear <= (int) $fromYear) {
            return response()->json(['error' => 'to_year يجب أن يكون أكبر من from_year'], 422);
        }

        DB::beginTransaction();
        try {
            $tableName = (new Order())->getTable();
            $fillable  = (new Order())->getFillable();

            // جلب الطلبات المتبقية: غير مسلَّمة (Reseved=0) وغير محاسَبة (Billed=0)
            $pending = DB::table($tableName)
                         ->where('Year', $fromYear)
                         ->where('Reseved', 0)
                         ->where('Billed', 0)
                         ->get();

            if ($pending->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'message' => 'لا توجد طلبات متبقية في سنة ' . $fromYear,
                    'rolled'  => 0,
                ]);
            }

            $rolled  = 0;
            $skipped = 0; // طلبات مكررة (موجودة مسبقاً بنفس ID في السنة الجديدة)

            foreach ($pending as $row) {
                $oldId = $row->ID;

                // تجنب التكرار إن سبق تدوير هذا الطلب
                $alreadyExists = DB::table($tableName)
                    ->where('ID', $oldId)
                    ->where('Year', $toYear)
                    ->exists();

                if ($alreadyExists) {
                    $skipped++;
                    continue;
                }

                $newId = (DB::table($tableName)->max('ID') ?? 0) + 1;

                $newData = array_intersect_key((array) $row, array_flip($fillable));
                $newData['ID']         = $newId;
                $newData['Year']       = $toYear;
                $newData['marji3']     = "{$oldId}/{$toYear}";
                $newData['OldID']      = $oldId;
                $newData['OldYear']    = $fromYear;
                $newData['Printed']    = 0;
                $newData['Billed']     = 0;
                $newData['Reseved']    = 0;
                $newData['delev_date'] = null;

                DB::table($tableName)->insert($newData);

                // نسخ الجداول المرتبطة
                $this->copyRelatedTables($oldId, $fromYear, $newId, $toYear);

                $rolled++;
            }

            DB::commit();

            return response()->json([
                'message'   => "تم تدوير الطلبات من {$fromYear} إلى {$toYear}",
                'rolled'    => $rolled,
                'skipped'   => $skipped,
                'from_year' => (int) $fromYear,
                'to_year'   => (int) $toYear,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("خطأ في تدوير الطلبات: " . $e->getMessage());
            return response()->json(['error' => 'حدث خطأ: ' . $e->getMessage()], 500);
        }
    }

    /**
     * ─────────────────────────────────────────────────────────────────────
     * حفظ شامل للطلب وجميع جداوله الفرعية في طلب واحد
     *
     * PUT /api/orders/{id}/{year}/save-all
     * Body: {
     *   "order":     { ...حقول الطلب الرئيسي... },
     *   "vouchers":  [ ...سندات... ],
     *   "actions":   [ ...حركات... ],
     *   "cartons":   [ ...كراتين... ],
     *   "materials": [ ...مواد... ],
     *   "problems":  [ ...مشاكل... ]
     * }
     * ─────────────────────────────────────────────────────────────────────
     */
    public function saveAll(Request $request, $id, $year): JsonResponse
    {
        DB::beginTransaction();
        try {
            $order = Order::where('ID', $id)->where('Year', $year)->firstOrFail();

            // ── 1. تحديث الطلب الرئيسي ────────────────────
            if ($request->has('order')) {
                $orderData = $request->input('order');

                $boolFields = [
                    'Printed','Billed','DubelM','varnich','uv_Spot','uv',
                    'seluvan_lum','seluvan_mat','Tay','Tad3em','harary',
                    'rolling','rollingBack','Reseved',
                ];
                foreach ($boolFields as $f) {
                    if (isset($orderData[$f])) {
                        $orderData[$f] = filter_var($orderData[$f], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
                    }
                }

                $fillable  = $order->getFillable();
                $orderData = array_intersect_key($orderData, array_flip($fillable));

                DB::table($order->getTable())
                    ->where('ID', $id)->where('Year', $year)
                    ->update($orderData);
            }

            // ── 2. vouchers ───────────────────────────────
            if ($request->has('vouchers')) {
                foreach ($request->input('vouchers') as $row) {
                    if (!empty($row['_delete']) && !empty($row['Voucher_num'])) {
                        DB::table('vouchers')->where('Voucher_num', $row['Voucher_num'])->delete();
                        continue;
                    }
                    $v = array_intersect_key($row, array_flip((new \App\Models\Voucher())->getFillable()));
                    $v['ID'] = $id; $v['Year'] = $year;
                    if (!empty($row['Voucher_num'])) {
                        $upd = $v; unset($upd['ID'], $upd['Year']);
                        DB::table('vouchers')->where('Voucher_num', $row['Voucher_num'])->update($upd);
                    } else {
                        $v['Voucher_num'] = (DB::table('vouchers')->max('Voucher_num') ?? 0) + 1;
                        DB::table('vouchers')->insert($v);
                    }
                }
            }

            // ── 3. actions ────────────────────────────────
            if ($request->has('actions')) {
                $allowedA = ['ID','year','Action','Color','Qunt_Ac','On','Machin',
                             'Hours','Kelo','Actual','Tarkeb','Wash','Electricity',
                             'Taghez','StopVar','Date','NotesA','Tabrer'];
                foreach ($request->input('actions') as $row) {
                    if (!empty($row['_delete']) && !empty($row['ID1'])) {
                        DB::table('actions')->where('ID1', $row['ID1'])->delete();
                        continue;
                    }
                    $a = array_intersect_key($row, array_flip($allowedA));
                    $a['ID'] = $id; $a['year'] = $year;
                    if (!empty($row['ID1'])) {
                        $upd = $a; unset($upd['ID'], $upd['year']);
                        DB::table('actions')->where('ID1', $row['ID1'])->update($upd);
                    } else {
                        $a['ID1'] = (DB::table('actions')->max('ID1') ?? 0) + 1;
                        DB::table('actions')->insert($a);
                    }
                }
            }

            // ── 4. cartons ────────────────────────────────
            if ($request->has('cartons')) {
                $allowedC = ['ID','year','Type1','Id_carton','Source1','Supplier1',
                             'Long1','Width1','Gramage1','Sheet_count1',
                             'Out_Date','Out_ord_num','note_crt','Price'];
                foreach ($request->input('cartons') as $row) {
                    if (!empty($row['_delete']) && !empty($row['ID1'])) {
                        DB::table('Carton')->where('ID1', $row['ID1'])->delete();
                        continue;
                    }
                    $c = array_intersect_key($row, array_flip($allowedC));
                    $c['ID'] = $id; $c['year'] = $year;
                    if (!empty($row['ID1'])) {
                        $upd = $c; unset($upd['ID'], $upd['year']);
                        DB::table('Carton')->where('ID1', $row['ID1'])->update($upd);
                    } else {
                        $c['ID1'] = (DB::table('Carton')->max('ID1') ?? 0) + 1;
                        DB::table('Carton')->insert($c);
                    }
                }
            }

            // ── 5. materials ──────────────────────────────
            if ($request->has('materials')) {
                $matFillable = (new \App\Models\Material())->getFillable();
                foreach ($request->input('materials') as $row) {
                    if (!empty($row['_delete']) && !empty($row['_ID'])) {
                        DB::table('materials')->where('_ID', $row['_ID'])->delete();
                        continue;
                    }
                    $m = array_intersect_key($row, array_flip($matFillable));
                    $m['ID'] = $id; $m['Year'] = $year;
                    if (!empty($row['_ID'])) {
                        $upd = $m; unset($upd['ID'], $upd['Year']);
                        DB::table('materials')->where('_ID', $row['_ID'])->update($upd);
                    } else {
                        DB::table('materials')->insert($m);
                    }
                }
            }

            // ── 6. problems ───────────────────────────────
            if ($request->has('problems')) {
                $probFillable = (new \App\Models\Problem())->getFillable();
                foreach ($request->input('problems') as $row) {
                    if (!empty($row['_delete']) && !empty($row['_ID'])) {
                        DB::table('problems')->where('_ID', $row['_ID'])->delete();
                        continue;
                    }
                    $p = array_intersect_key($row, array_flip($probFillable));
                    $p['ID'] = $id; $p['Year'] = $year;
                    if (!empty($row['_ID'])) {
                        $upd = $p; unset($upd['ID'], $upd['Year']);
                        DB::table('problems')->where('_ID', $row['_ID'])->update($upd);
                    } else {
                        DB::table('problems')->insert($p);
                    }
                }
            }

            DB::commit();

            // إعادة الطلب كاملاً مع العلاقات
            $updated = Order::where('ID', $id)->where('Year', $year)->first();
            $updated->load('vouchers');

            return response()->json([
                'message' => 'تم الحفظ الشامل بنجاح',
                'order'   => $updated,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'الطلب غير موجود'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("خطأ في الحفظ الشامل: " . $e->getMessage());
            return response()->json(['error' => 'حدث خطأ: ' . $e->getMessage()], 500);
        }
    }

    /**
     * ─────────────────────────────────────────────────────────────────────
     * حساب عدد الطبع
     *
     * POST /api/orders/calculate-print
     * Body (يدوي):  { "Demand": 10000, "cut": 4, "Equation": 1.03 }
     * Body (تلقائي من طلب): { "order_id": 123, "year": 2026 }
     *
     * المعادلات:
     *   عدد الأطباق  = ceil( Demand * 1.03 )   ← يفصل الطبق × 3%
     *   عدد الطبع    = عدد الأطباق × cut
     * ─────────────────────────────────────────────────────────────────────
     */
    public function calculatePrint(Request $request): JsonResponse
    {
        try {
            $demand   = null;
            $cut      = null;
            $equation = 1.03; // النسبة الافتراضية 3%

            // ── وضع التلقائي: من طلب موجود ───────────────
            if ($request->filled('order_id')) {
                $orderYear = $request->input('year', date('Y'));
                $order = DB::table((new Order())->getTable())
                            ->where('ID', $request->input('order_id'))
                            ->where('Year', $orderYear)
                            ->first();

                if (!$order) {
                    return response()->json(['error' => 'الطلب غير موجود'], 404);
                }

                $demand   = (float) $order->Demand;
                // cut1 أو cut2 — يُستخدم cut1 إن وُجد وإلا cut2
                $cut      = !empty($order->cut1) ? (float) $order->cut1 : (float) ($order->cut2 ?? 1);
                $equation = !empty($order->Equation) ? (float) $order->Equation : 1.03;

            } else {
                // ── وضع اليدوي: قيم مباشرة من الـ body ───
                $demand   = (float) $request->input('Demand', 0);
                $cut      = (float) $request->input('cut', 1);
                $equation = (float) $request->input('Equation', 1.03);
            }

            if ($demand <= 0) {
                return response()->json(['error' => 'Demand يجب أن يكون أكبر من صفر'], 422);
            }
            if ($cut <= 0) {
                return response()->json(['error' => 'cut يجب أن يكون أكبر من صفر'], 422);
            }

            //  المعادلات
            //  عدد الأطباق = ceil( Demand × Equation )  ← الطبق يُفصَّل بنسبة 3%
            //  عدد الطبع   = عدد الأطباق × cut
            $numPlates = (int) ceil($demand * $equation);
            $numPrint  = $numPlates * $cut;

            return response()->json([
                'inputs' => [
                    'Demand'   => $demand,
                    'cut'      => $cut,
                    'Equation' => $equation,
                ],
                'results' => [
                    'num_plates' => $numPlates,   // عدد الأطباق
                    'num_print'  => $numPrint,     // عدد الطبع
                ],
                'formula' => [
                    'num_plates' => "ceil({$demand} × {$equation}) = {$numPlates}",
                    'num_print'  => "{$numPlates} × {$cut} = {$numPrint}",
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper مشترك: ينسخ الجداول المرتبطة من طلب إلى آخر
     * يُستخدَم من copy() و rollover()
     */
    private function copyRelatedTables(int $oldId, $oldYear, int $newId, $newYear): void
    {
        // vouchers
        $vouchers = DB::table('vouchers')->where('ID', $oldId)->where('Year', $oldYear)->get();
        $vFill = (new \App\Models\Voucher())->getFillable();
        foreach ($vouchers as $row) {
            $v = array_intersect_key((array) $row, array_flip($vFill));
            $v['ID']          = $newId;
            $v['Year']        = $newYear;
            $v['Voucher_num'] = (DB::table('vouchers')->max('Voucher_num') ?? 0) + 1;
            DB::table('vouchers')->insert($v);
        }

        // actions
        $actions = DB::table('actions')->where('ID', $oldId)->where('year', $oldYear)->get();
        $aFill = ['ID1', 'ID', 'year', 'Action', 'Color', 'Qunt_Ac', 'On', 'Machin',
                  'Hours', 'Date', 'NotesA', 'Kelo', 'Actual', 'Tarkeb', 'Wash',
                  'Electricity', 'Taghez', 'StopVar', 'Tabrer'];
        foreach ($actions as $row) {
            $a = array_intersect_key((array) $row, array_flip($aFill));
            unset($a['ID1']); // PK خاص بالحركة — يُحسب من جديد، لا يُنسخ
            $a['ID']   = $newId;
            $a['year'] = $newYear;
            $a['ID1']  = (DB::table('actions')->max('ID1') ?? 0) + 1;
            DB::table('actions')->insert($a);
        }

        // Carton
        $cartons = DB::table('Carton')->where('ID', $oldId)->where('year', $oldYear)->get();
        $cFill = (new \App\Models\Carton())->getFillable();
        foreach ($cartons as $row) {
            $c = array_intersect_key((array) $row, array_flip($cFill));
            $c['ID']   = $newId;
            $c['year'] = $newYear;
            $c['ID1']  = (DB::table('Carton')->max('ID1') ?? 0) + 1;
            DB::table('Carton')->insert($c);
        }

        // materials
        $materials = DB::table('materials')->where('ID', $oldId)->where('Year', $oldYear)->get();
        $mFill = (new \App\Models\Material())->getFillable();
        foreach ($materials as $row) {
            $m = array_intersect_key((array) $row, array_flip($mFill));
            $m['ID']   = $newId;
            $m['Year'] = $newYear;
            DB::table('materials')->insert($m);
        }

        // problems
        $problems = DB::table('problems')->where('ID', $oldId)->where('Year', $oldYear)->get();
        $pFill = (new \App\Models\Problem())->getFillable();
        foreach ($problems as $row) {
            $p = array_intersect_key((array) $row, array_flip($pFill));
            $p['ID']   = $newId;
            $p['Year'] = $newYear;
            DB::table('problems')->insert($p);
        }
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
