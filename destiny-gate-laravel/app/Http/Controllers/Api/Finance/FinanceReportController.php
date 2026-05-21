<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceReportController extends Controller
{
    /* ── Dashboard Summary ────────────────────────────────────────────────── */

    public function dashboardSummary(Request $request)
    {
        $yearId = $request->academic_year_id;
        $termId = $request->term_id;

        $billsQ = DB::table('student_bills')->where('status', '!=', 'cancelled');
        $paymQ  = DB::table('finance_payments');

        if ($yearId) { $billsQ->where('academic_year_id', $yearId); $paymQ->where('academic_year_id', $yearId); }
        if ($termId) { $billsQ->where('term_id', $termId);          $paymQ->where('term_id', $termId); }

        $totalExpected   = (clone $billsQ)->sum('amount');
        $totalCollected  = (clone $paymQ)->sum('amount');
        $totalOutstanding= (clone $billsQ)->sum('balance');
        $fullyPaid       = (clone $billsQ)->where('status', 'paid')->distinct('student_id')->count('student_id');
        $debtors         = (clone $billsQ)->whereIn('status', ['unpaid', 'partial'])->distinct('student_id')->count('student_id');
        $collectionRate  = $totalExpected > 0 ? round(($totalCollected / $totalExpected) * 100, 1) : 0;

        return response()->json([
            'total_expected'    => $totalExpected,
            'total_collected'   => $totalCollected,
            'total_outstanding' => $totalOutstanding,
            'collection_rate'   => $collectionRate,
            'fully_paid'        => $fullyPaid,
            'debtors'           => $debtors,
        ]);
    }

    /* ── Daily Collections ────────────────────────────────────────────────── */

    public function dailyCollections(Request $request)
    {
        $date = $request->date ?? date('Y-m-d');
        $payments = DB::table('finance_payments as fp')
            ->join('students', 'fp.student_id', '=', 'students.id')
            ->join('users', 'fp.received_by', '=', 'users.id')
            ->select('fp.*',
                DB::raw("CONCAT(students.first_name,' ',students.last_name) as student_name"),
                'students.student_number',
                'users.name as received_by_name'
            )
            ->whereDate('fp.payment_date', $date)
            ->orderByDesc('fp.id')
            ->get();

        $total = $payments->sum('amount');
        $byMethod = $payments->groupBy('payment_method')->map(fn($g) => $g->sum('amount'));

        return response()->json(['date' => $date, 'total' => $total, 'by_method' => $byMethod, 'payments' => $payments]);
    }

    /* ── Monthly Collections ──────────────────────────────────────────────── */

    public function monthlyCollections(Request $request)
    {
        $year  = $request->year  ?? date('Y');
        $month = $request->month ?? date('m');

        $payments = DB::table('finance_payments')
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->selectRaw('DATE(payment_date) as date, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $total = $payments->sum('total');
        return response()->json(['year' => $year, 'month' => $month, 'total' => $total, 'daily' => $payments]);
    }

    /* ── Term Collections ─────────────────────────────────────────────────── */

    public function termCollections(Request $request)
    {
        $request->validate(['academic_year_id' => 'required', 'term_id' => 'required']);

        $payments = DB::table('finance_payments as fp')
            ->join('students', 'fp.student_id', '=', 'students.id')
            ->join('users', 'fp.received_by', '=', 'users.id')
            ->select('fp.*',
                DB::raw("CONCAT(students.first_name,' ',students.last_name) as student_name"),
                'students.student_number',
                'users.name as received_by_name'
            )
            ->where('fp.academic_year_id', $request->academic_year_id)
            ->where('fp.term_id', $request->term_id)
            ->orderByDesc('fp.payment_date')
            ->get();

        $total    = $payments->sum('amount');
        $byMethod = $payments->groupBy('payment_method')->map(fn($g) => $g->sum('amount'));

        return response()->json(['total' => $total, 'by_method' => $byMethod, 'payments' => $payments]);
    }

    /* ── Debtors ──────────────────────────────────────────────────────────── */

    public function debtors(Request $request)
    {
        $q = DB::table('student_bills as sb')
            ->join('students', 'sb.student_id', '=', 'students.id')
            ->leftJoin('classes', 'students.class_id', '=', 'classes.id')
            ->leftJoin('forms', 'classes.form_id', '=', 'forms.id')
            ->select(
                'students.id as student_id',
                DB::raw("CONCAT(students.first_name,' ',students.last_name) as student_name"),
                'students.student_number',
                'classes.class_name',
                'forms.name as form_name',
                DB::raw('SUM(sb.balance) as total_balance'),
                DB::raw('SUM(sb.amount) as total_billed'),
                DB::raw('SUM(sb.amount_paid) as total_paid')
            )
            ->whereIn('sb.status', ['unpaid', 'partial'])
            ->groupBy('students.id', 'students.first_name', 'students.last_name', 'students.student_number', 'classes.class_name', 'forms.name')
            ->orderByDesc('total_balance');

        if ($request->academic_year_id) $q->where('sb.academic_year_id', $request->academic_year_id);
        if ($request->term_id)          $q->where('sb.term_id', $request->term_id);
        if ($request->form_id)          $q->where('forms.id', $request->form_id);

        return response()->json($q->get());
    }

    /* ── Fully Paid ───────────────────────────────────────────────────────── */

    public function fullyPaid(Request $request)
    {
        $q = DB::table('students')
            ->leftJoin('classes', 'students.class_id', '=', 'classes.id')
            ->leftJoin('forms', 'classes.form_id', '=', 'forms.id')
            ->whereNotExists(function ($sub) use ($request) {
                $sub->from('student_bills')
                    ->whereColumn('student_bills.student_id', 'students.id')
                    ->whereIn('student_bills.status', ['unpaid', 'partial']);
                if ($request->academic_year_id) $sub->where('student_bills.academic_year_id', $request->academic_year_id);
                if ($request->term_id)          $sub->where('student_bills.term_id', $request->term_id);
            })
            ->whereExists(function ($sub) use ($request) {
                $sub->from('student_bills')
                    ->whereColumn('student_bills.student_id', 'students.id')
                    ->where('student_bills.status', 'paid');
                if ($request->academic_year_id) $sub->where('student_bills.academic_year_id', $request->academic_year_id);
                if ($request->term_id)          $sub->where('student_bills.term_id', $request->term_id);
            })
            ->where('students.status', 'active')
            ->select('students.id', DB::raw("CONCAT(students.first_name,' ',students.last_name) as student_name"), 'students.student_number', 'classes.class_name', 'forms.name as form_name')
            ->orderBy('students.first_name');

        return response()->json($q->get());
    }

    /* ── Partially Paid ───────────────────────────────────────────────────── */

    public function partiallyPaid(Request $request)
    {
        return $this->debtors($request); // same logic, debtors = partial + unpaid
    }

    /* ── Balances by Form ─────────────────────────────────────────────────── */

    public function balancesByForm(Request $request)
    {
        $q = DB::table('student_bills as sb')
            ->join('students', 'sb.student_id', '=', 'students.id')
            ->join('classes', 'students.class_id', '=', 'classes.id')
            ->join('forms', 'classes.form_id', '=', 'forms.id')
            ->select(
                'forms.id as form_id',
                'forms.name as form_name',
                'forms.level',
                DB::raw('SUM(sb.amount) as total_billed'),
                DB::raw('SUM(sb.amount_paid) as total_paid'),
                DB::raw('SUM(sb.balance) as total_balance'),
                DB::raw('COUNT(DISTINCT sb.student_id) as student_count')
            )
            ->where('sb.status', '!=', 'cancelled')
            ->groupBy('forms.id', 'forms.name', 'forms.level')
            ->orderBy('forms.level');

        if ($request->academic_year_id) $q->where('sb.academic_year_id', $request->academic_year_id);
        if ($request->term_id)          $q->where('sb.term_id', $request->term_id);

        return response()->json($q->get());
    }

    /* ── Student Statement ────────────────────────────────────────────────── */

    public function studentStatement(int $studentId, Request $request)
    {
        $student = DB::table('students')
            ->leftJoin('classes', 'students.class_id', '=', 'classes.id')
            ->select('students.*', 'classes.class_name', 'classes.stream')
            ->where('students.id', $studentId)->first();

        abort_if(!$student, 404, 'Student not found.');

        $q = DB::table('student_account_transactions')
            ->where('student_id', $studentId)
            ->orderBy('created_at');

        if ($request->academic_year_id) $q->where('academic_year_id', $request->academic_year_id);
        if ($request->term_id)          $q->where('term_id', $request->term_id);

        $transactions = $q->get();

        $totalBilled  = DB::table('student_bills')->where('student_id', $studentId)->where('status', '!=', 'cancelled')->sum('amount');
        $totalPaid    = DB::table('student_bills')->where('student_id', $studentId)->where('status', '!=', 'cancelled')->sum('amount_paid');
        $totalBalance = DB::table('student_bills')->where('student_id', $studentId)->where('status', '!=', 'cancelled')->sum('balance');

        return response()->json([
            'student'       => $student,
            'transactions'  => $transactions,
            'summary'       => ['total_billed' => $totalBilled, 'total_paid' => $totalPaid, 'total_balance' => $totalBalance],
        ]);
    }
}
