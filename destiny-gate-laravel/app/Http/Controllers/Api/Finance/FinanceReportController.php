<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Support\StudentStreamResolver;
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
                'students.first_name as student_first_name',
                'students.last_name as student_last_name',
                'students.student_number',
                'users.name as received_by_name'
            )
            ->whereDate('fp.payment_date', $date)
            ->orderByDesc('fp.id')
            ->get();
        foreach ($payments as $p) {
            $p->student_name = trim(($p->student_first_name ?? '') . ' ' . ($p->student_last_name ?? ''));
        }

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
                'students.first_name as student_first_name',
                'students.last_name as student_last_name',
                'students.student_number',
                'users.name as received_by_name'
            )
            ->where('fp.academic_year_id', $request->academic_year_id)
            ->where('fp.term_id', $request->term_id)
            ->orderByDesc('fp.payment_date')
            ->get();
        foreach ($payments as $p) {
            $p->student_name = trim(($p->student_first_name ?? '') . ' ' . ($p->student_last_name ?? ''));
        }

        $total    = $payments->sum('amount');
        $byMethod = $payments->groupBy('payment_method')->map(fn($g) => $g->sum('amount'));

        return response()->json(['total' => $total, 'by_method' => $byMethod, 'payments' => $payments]);
    }

    /* ── Debtors ──────────────────────────────────────────────────────────── */

    public function debtors(Request $request)
    {
        $q = DB::table('student_bills as sb')
            ->join('students', 'sb.student_id', '=', 'students.id')
            ->select(
                'students.id as student_id',
                'students.first_name as student_first_name',
                'students.last_name as student_last_name',
                'students.student_number',
                DB::raw('SUM(sb.balance) as total_balance'),
                DB::raw('SUM(sb.amount) as total_billed'),
                DB::raw('SUM(sb.amount_paid) as total_paid')
            )
            ->whereIn('sb.status', ['unpaid', 'partial'])
            ->groupBy('students.id', 'students.first_name', 'students.last_name', 'students.student_number')
            ->orderByDesc('total_balance');

        if ($request->academic_year_id) $q->where('sb.academic_year_id', $request->academic_year_id);
        if ($request->term_id)          $q->where('sb.term_id', $request->term_id);

        $rows = $q->get();
        foreach ($rows as $row) {
            $row->student_name = trim(($row->student_first_name ?? '') . ' ' . ($row->student_last_name ?? ''));
            StudentStreamResolver::attachResolvedFields($row);
        }

        if ($request->form_id) {
            $rows = $rows->filter(fn ($row) => (int) $row->form_id === (int) $request->form_id)->values();
        }

        return response()->json($rows);
    }

    /* ── Fully Paid ───────────────────────────────────────────────────────── */

    public function fullyPaid(Request $request)
    {
        $q = DB::table('students')
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
            ->select('students.id', 'students.first_name', 'students.last_name', 'students.student_number')
            ->orderBy('students.first_name');

        $rows = $q->get();
        foreach ($rows as $row) {
            $row->student_name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
            StudentStreamResolver::attachResolvedFields($row);
        }

        if ($request->form_id) {
            $rows = $rows->filter(fn ($row) => (int) $row->form_id === (int) $request->form_id)->values();
        }

        return response()->json($rows);
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
            ->select(
                'sb.student_id',
                DB::raw('SUM(sb.amount) as total_billed'),
                DB::raw('SUM(sb.amount_paid) as total_paid'),
                DB::raw('SUM(sb.balance) as total_balance')
            )
            ->where('sb.status', '!=', 'cancelled')
            ->groupBy('sb.student_id');

        if ($request->academic_year_id) $q->where('sb.academic_year_id', $request->academic_year_id);
        if ($request->term_id)          $q->where('sb.term_id', $request->term_id);

        $byStudent = $q->get();

        $groups = [];
        foreach ($byStudent as $row) {
            StudentStreamResolver::attachResolvedFields($row);
            $formId = $row->form_id;
            $key = $formId ? (string) $formId : 'null';
            if (!isset($groups[$key])) {
                $groups[$key] = (object) [
                    'form_id' => $row->form_id,
                    'form_name' => $row->form_name,
                    'level' => null,
                    'total_billed' => 0,
                    'total_paid' => 0,
                    'total_balance' => 0,
                    'student_count' => 0,
                ];
            }
            $groups[$key]->total_billed += (float) $row->total_billed;
            $groups[$key]->total_paid += (float) $row->total_paid;
            $groups[$key]->total_balance += (float) $row->total_balance;
            $groups[$key]->student_count++;
        }

        $formIds = collect($groups)->pluck('form_id')->filter()->values()->all();
        $levelsById = empty($formIds)
            ? collect()
            : DB::table('forms')->whereIn('id', $formIds)->pluck('level', 'id');

        foreach ($groups as $group) {
            if ($group->form_id) {
                $group->level = $levelsById[(int) $group->form_id] ?? null;
            }
        }

        $rows = collect($groups)->values()->sortBy(fn ($r) => $r->level ?? PHP_INT_MAX)->values();
        return response()->json($rows);
    }

    /* ── Student Statement ────────────────────────────────────────────────── */

    public function studentStatement(int $studentId, Request $request)
    {
        $student = DB::table('students')
            ->select('students.*')
            ->where('students.id', $studentId)->first();

        abort_if(!$student, 404, 'Student not found.');
        $student = StudentStreamResolver::attachResolvedFields($student);

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
