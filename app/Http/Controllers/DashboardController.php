<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $year = $request->get('year', date('Y'));

        $base = fn() => Order::where('Year', $year);

        $total     = $base()->count();
        $printed   = $base()->where('Printed', 'True')->count();
        $billed    = $base()->where('Billed',  'True')->count();
        $delivered = $base()->whereNotNull('Reseved')
                            ->where('Reseved', '!=', '')
                            ->where('Reseved', '!=', '0')
                            ->count();

        // Monthly breakdown (orders per month by date_come)
        $monthly = Order::where('Year', $year)
            ->whereNotNull('date_come')
            ->select(DB::raw("MONTH(date_come) as month"), DB::raw('COUNT(*) as count'))
            ->groupByRaw("MONTH(date_come)")
            ->orderBy('month')
            ->pluck('count', 'month');

        // Recent 10 orders
        $recent = $base()
            ->select(['row_id','ID','Year','Customer','Eng_Name','date_come','Demand','Printed','Billed','Reseved'])
            ->orderByDesc('row_id')
            ->limit(10)
            ->get();

        // Available years
        $years = Order::select('Year')
            ->whereNotNull('Year')->where('Year', '!=', '')
            ->distinct()->orderByDesc('Year')
            ->pluck('Year');

        return response()->json(compact('total','printed','billed','delivered','monthly','recent','years'));
    }
}
