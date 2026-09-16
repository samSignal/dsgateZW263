<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Api\Finance\Concerns\ExportsReports;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopReportController extends Controller
{
    use ExportsReports;

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

        $total = $purchases->sum('total_amount');

        if ($request->format === 'pdf') {
            return $this->exportPdf('reports.shop.sales-register', ['purchases' => $purchases, 'total' => $total, 'from' => $from, 'to' => $to], "shop-sales-{$from}-to-{$to}.pdf");
        }
        if ($request->format === 'csv') {
            return $this->exportCsv("shop-sales-{$from}-to-{$to}.csv",
                ['#', 'Date', 'Purchase #', 'Student', 'Total'],
                $purchases->values()->map(fn ($p, $i) => [$i + 1, $p->purchase_date, $p->purchase_number, $p->student_name, $p->total_amount])
            );
        }

        return response()->json(['date_from' => $from, 'date_to' => $to, 'total' => $total, 'purchases' => $purchases]);
    }

    public function salesByItem(Request $request)
    {
        $q = DB::table('student_purchase_items as spi')
            ->join('student_purchases as sp', 'spi.student_purchase_id', '=', 'sp.id')
            ->join('shop_items as si', 'spi.shop_item_id', '=', 'si.id')
            ->leftJoin('shop_categories as sc', 'si.shop_category_id', '=', 'sc.id')
            ->select('si.id', 'si.item_name', 'si.item_code', 'sc.id as category_id', 'sc.name as category_name',
                DB::raw('SUM(spi.quantity) as quantity_sold'), DB::raw('SUM(spi.line_total) as total_sales'))
            ->where('sp.status', '!=', 'cancelled')
            ->groupBy('si.id', 'si.item_name', 'si.item_code', 'sc.id', 'sc.name')
            ->orderByDesc('total_sales');

        if ($request->date_from) $q->where('sp.purchase_date', '>=', $request->date_from);
        if ($request->date_to) $q->where('sp.purchase_date', '<=', $request->date_to);
        if ($request->category_id) $q->where('sc.id', $request->category_id);

        $rows = $q->get();

        if ($request->format === 'pdf') {
            return $this->exportPdf('reports.shop.sales-by-item', compact('rows'), 'shop-sales-by-item.pdf');
        }
        if ($request->format === 'csv') {
            return $this->exportCsv('shop-sales-by-item.csv',
                ['#', 'Item', 'Code', 'Category', 'Qty Sold', 'Total Sales'],
                $rows->values()->map(fn ($r, $i) => [$i + 1, $r->item_name, $r->item_code, $r->category_name, $r->quantity_sold, $r->total_sales])
            );
        }

        return response()->json($rows);
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

        $rows = $q->get();

        if ($request->format === 'pdf') {
            return $this->exportPdf('reports.shop.sales-by-category', compact('rows'), 'shop-sales-by-category.pdf');
        }
        if ($request->format === 'csv') {
            return $this->exportCsv('shop-sales-by-category.csv',
                ['#', 'Category', 'Qty Sold', 'Total Sales'],
                $rows->values()->map(fn ($r, $i) => [$i + 1, $r->name, $r->quantity_sold, $r->total_sales])
            );
        }

        return response()->json($rows);
    }

    public function unpaidPurchases(Request $request)
    {
        $rows = DB::table('student_purchases as sp')
            ->join('students as s', 'sp.student_id', '=', 's.id')
            ->select('sp.*', DB::raw("CONCAT(s.first_name,' ',s.last_name) as student_name"), 's.student_number')
            ->whereIn('sp.status', ['unpaid', 'partial'])
            ->orderByDesc('sp.balance')
            ->get();

        if ($request->format === 'pdf') {
            return $this->exportPdf('reports.shop.unpaid-purchases', compact('rows'), 'shop-unpaid-purchases.pdf');
        }
        if ($request->format === 'csv') {
            return $this->exportCsv('shop-unpaid-purchases.csv',
                ['#', 'Student', 'Student #', 'Purchase #', 'Total', 'Paid', 'Balance', 'Status'],
                $rows->values()->map(fn ($r, $i) => [$i + 1, $r->student_name, $r->student_number, $r->purchase_number, $r->total_amount, $r->amount_paid, $r->balance, $r->status])
            );
        }

        return response()->json($rows);
    }

    public function lowStockItems(Request $request)
    {
        $rows = DB::table('shop_items as si')
            ->leftJoin('shop_categories as sc', 'si.shop_category_id', '=', 'sc.id')
            ->select('si.*', 'sc.name as category_name')
            ->whereColumn('si.quantity_in_stock', '<=', 'si.reorder_level')
            ->orderBy('si.quantity_in_stock')
            ->get();

        if ($request->format === 'pdf') {
            return $this->exportPdf('reports.shop.low-stock', compact('rows'), 'shop-low-stock.pdf');
        }
        if ($request->format === 'csv') {
            return $this->exportCsv('shop-low-stock.csv',
                ['#', 'Item', 'Code', 'Category', 'In Stock', 'Reorder Level'],
                $rows->values()->map(fn ($r, $i) => [$i + 1, $r->item_name, $r->item_code, $r->category_name, $r->quantity_in_stock, $r->reorder_level])
            );
        }

        return response()->json($rows);
    }

    public function cancelledPurchases(Request $request)
    {
        $rows = DB::table('student_purchases as sp')
            ->join('students as s', 'sp.student_id', '=', 's.id')
            ->select('sp.*', DB::raw("CONCAT(s.first_name,' ',s.last_name) as student_name"), 's.student_number')
            ->where('sp.status', 'cancelled')
            ->orderByDesc('sp.updated_at')
            ->get();

        if ($request->format === 'pdf') {
            return $this->exportPdf('reports.shop.cancelled-purchases', compact('rows'), 'shop-cancelled-purchases.pdf');
        }
        if ($request->format === 'csv') {
            return $this->exportCsv('shop-cancelled-purchases.csv',
                ['#', 'Date', 'Purchase #', 'Student', 'Total'],
                $rows->values()->map(fn ($r, $i) => [$i + 1, $r->purchase_date, $r->purchase_number, $r->student_name, $r->total_amount])
            );
        }

        return response()->json($rows);
    }
}
