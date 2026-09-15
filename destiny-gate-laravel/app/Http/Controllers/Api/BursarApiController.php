<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class BursarApiController extends Controller
{
    public function dashboard()
    {
        // Real Finance module data (finance_payments/student_bills) — the legacy
        // Payment/StudentFee models this dashboard used to read from had a couple of
        // leftover demo rows each and were never where actual billing happened; the rest
        // of that legacy surface (fees/payments/fee-structures/debtors/paid endpoints) has
        // been removed in favor of the modern Finance module, which bursar now has direct
        // access to.
        $recentPayments = DB::table('finance_payments as fp')
            ->join('students as s', 'fp.student_id', '=', 's.id')
            ->where('fp.status', 'active')
            ->orderByDesc('fp.payment_date')
            ->orderByDesc('fp.id')
            ->limit(10)
            ->get(['fp.id', 'fp.amount', 'fp.payment_method', 'fp.payment_date', 's.first_name', 's.last_name'])
            ->map(fn ($p) => [
                'id'             => $p->id,
                'amount'         => $p->amount,
                'payment_method' => $p->payment_method,
                'payment_date'   => $p->payment_date,
                'student'        => ['first_name' => $p->first_name, 'last_name' => $p->last_name],
            ]);

        return response()->json([
            'total_collected'   => (float) DB::table('finance_payments')->where('status', 'active')->whereYear('payment_date', date('Y'))->sum('amount'),
            'total_outstanding' => (float) DB::table('student_bills')->where('status', '!=', 'cancelled')->sum('balance'),
            'paid_in_full'      => DB::table('student_bills')->where('status', 'paid')->distinct()->count('student_id'),
            'debtors_count'     => DB::table('student_bills')->whereIn('status', ['unpaid', 'partial'])->distinct()->count('student_id'),
            'recent_payments'   => $recentPayments,
        ]);
    }
}
