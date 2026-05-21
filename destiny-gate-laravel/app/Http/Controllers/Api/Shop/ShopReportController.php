<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopReportController extends Controller
{
    public function dashboardSummary()
    {
        $today = date('Y-m-d');

        return response()->json([
            'today_sales' => DB::table('student_purchases')->where('purchase_date', $today)->where('status', '!=', 'cancelled')->sum('total_amount'),
            'total_purchases' => DB::table('student_purchases')->where('status', '!=', 'cancelled')->count(),
            'unpaid_purchases' => DB::table('student_purchases')->whereIn('status', ['unpaid', 'partial'])->count(),
            'low_stock_items' => DB::table('shop_items')->whereColumn('quantity_in_stock', '<=', 'reorder_level')->count(),
            'cancelled_purchases' => DB::table('student_purchases')->where('status', 'cancelled')->count(),
        ]);
    }

    public function salesToday()
    {
        return $this->salesByDateRange(new Request(['date_from' => date('Y-m-d'), 'date_to' => date('Y-m-d')]));
    }

    public function salesByDateRange(Request $request)
    {
        $from = $request->query('date_from', date('Y-m-d'));
        $to = $request->query('date_to', date('Y-m-d'));

        $purchases = DB::table('student_purchases as sp')
            ->join('students as s', 'sp.student_id', '=', 's.id')
            ->select('sp.*', DB::raw("CONCAT(s.first_name,' ',s.last_name) as student_name"), 's.student_number')
            ->whereBetween('sp.purchase_date', [$from, $to])
            ->where('sp.status', '!=', 'cancelled')
            ->orderByDesc('sp.purchase_date')
            ->get();

        return response()->json(['date_from' => $from, 'date_to' => $to, 'total' => $purchases->sum('total_amount'), 'purchases' => $purchases]);
    }

    public function salesByItem(Request $request)
    {
        $q = DB::table('student_purchase_items as spi')
            ->join('student_purchases as sp', 'spi.student_purchase_id', '=', 'sp.id')
            ->join('shop_items as si', 'spi.shop_item_id', '=', 'si.id')
            ->select('si.id', 'si.item_name', 'si.item_code', DB::raw('SUM(spi.quantity) as quantity_sold'), DB::raw('SUM(spi.line_total) as total_sales'))
            ->where('sp.status', '!=', 'cancelled')
            ->groupBy('si.id', 'si.item_name', 'si.item_code')
            ->orderByDesc('total_sales');

        if ($request->date_from) $q->where('sp.purchase_date', '>=', $request->date_from);
        if ($request->date_to) $q->where('sp.purchase_date', '<=', $request->date_to);

        return response()->json($q->get());
    }

    public function salesByCategory(Request $request)
    {
        $q = DB::table('student_purchase_items as spi')
            ->join('student_purchases as sp', 'spi.student_purchase_id', '=', 'sp.id')
            ->join('shop_items as si', 'spi.shop_item_id', '=', 'si.id')
            ->join('shop_categories as sc', 'si.shop_category_id', '=', 'sc.id')
            ->select('sc.id', 'sc.name', DB::raw('SUM(spi.quantity) as quantity_sold'), DB::raw('SUM(spi.line_total) as total_sales'))
            ->where('sp.status', '!=', 'cancelled')
            ->groupBy('sc.id', 'sc.name')
            ->orderByDesc('total_sales');

        if ($request->date_from) $q->where('sp.purchase_date', '>=', $request->date_from);
        if ($request->date_to) $q->where('sp.purchase_date', '<=', $request->date_to);

        return response()->json($q->get());
    }

    public function unpaidPurchases()
    {
        return response()->json(DB::table('student_purchases as sp')
            ->join('students as s', 'sp.student_id', '=', 's.id')
            ->select('sp.*', DB::raw("CONCAT(s.first_name,' ',s.last_name) as student_name"), 's.student_number')
            ->whereIn('sp.status', ['unpaid', 'partial'])
            ->orderByDesc('sp.balance')
            ->get());
    }

    public function lowStockItems()
    {
        return response()->json(DB::table('shop_items as si')
            ->leftJoin('shop_categories as sc', 'si.shop_category_id', '=', 'sc.id')
            ->select('si.*', 'sc.name as category_name')
            ->whereColumn('si.quantity_in_stock', '<=', 'si.reorder_level')
            ->orderBy('si.quantity_in_stock')
            ->get());
    }

    public function cancelledPurchases()
    {
        return response()->json(DB::table('student_purchases as sp')
            ->join('students as s', 'sp.student_id', '=', 's.id')
            ->select('sp.*', DB::raw("CONCAT(s.first_name,' ',s.last_name) as student_name"), 's.student_number')
            ->where('sp.status', 'cancelled')
            ->orderByDesc('sp.updated_at')
            ->get());
    }
}
