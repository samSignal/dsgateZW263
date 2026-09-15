<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Api\Finance\Concerns\ExportsReports;
use App\Http\Controllers\Controller;
use App\Support\FeeAccountService;
use App\Support\StudentStreamResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceReportController extends Controller
{
    use ExportsReports;

    /* ── Dashboard Summary ────────────────────────────────────────────────── */

    public function dashboardSummary(Request $request)
    {
        $yearId = $request->academic_year_id;
        $termId = $request->term_id;

        $billsQ = DB::table('student_bills')->where('status', '!=', 'cancelled');
        $paymQ  = DB::table('finance_payments')->where('status', 'active');

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
                'students.admission_number',
                'users.name as received_by_name'
            )
            ->where('fp.status', 'active')
            ->whereDate('fp.payment_date', $date)
            ->orderByDesc('fp.id')
            ->get();
        foreach ($payments as $p) {
            $p->student_name = trim(($p->student_first_name ?? '') . ' ' . ($p->student_last_name ?? ''));
        }

        $total = $payments->sum('amount');
        $byMethod = $payments->groupBy('payment_method')->map(fn($g) => $g->sum('amount'));

        if ($request->format === 'pdf') {
            return $this->exportPdf('reports.finance.daily-register', compact('date', 'total', 'payments'), "daily-register-{$date}.pdf");
        }
        if ($request->format === 'csv') {
            return $this->exportCsv("daily-register-{$date}.csv",
                ['Receipt #', 'Student', 'Method', 'Received By', 'Amount'],
                $payments->map(fn ($p) => [$p->receipt_number, $p->student_name, $p->payment_method, $p->received_by_name, $p->amount])
            );
        }

        return response()->json(['date' => $date, 'total' => $total, 'by_method' => $byMethod, 'payments' => $payments]);
    }

    /* ── Monthly Collections ──────────────────────────────────────────────── */

    public function monthlyCollections(Request $request)
    {
        $year  = $request->year  ?? date('Y');
        $month = $request->month ?? date('m');

        $payments = DB::table('finance_payments')
            ->where('status', 'active')
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
                'students.admission_number',
                'users.name as received_by_name'
            )
            ->where('fp.academic_year_id', $request->academic_year_id)
            ->where('fp.term_id', $request->term_id)
            ->where('fp.status', 'active')
            ->orderByDesc('fp.payment_date')
            ->get();
        foreach ($payments as $p) {
            $p->student_name = trim(($p->student_first_name ?? '') . ' ' . ($p->student_last_name ?? ''));
        }

        $total    = $payments->sum('amount');
        $byMethod = $payments->groupBy('payment_method')->map(fn($g) => $g->sum('amount'));

        if (in_array($request->format, ['pdf', 'csv'])) {
            $academicYearName = DB::table('academic_years')->where('id', $request->academic_year_id)->value('name');
            $termName         = DB::table('terms')->where('id', $request->term_id)->value('name');

            if ($request->format === 'pdf') {
                return $this->exportPdf('reports.finance.term-collection',
                    compact('payments', 'total', 'academicYearName', 'termName'),
                    "term-collection-{$academicYearName}-{$termName}.pdf");
            }
            return $this->exportCsv("term-collection-{$academicYearName}-{$termName}.csv",
                ['Receipt #', 'Student', 'Method', 'Date', 'Amount'],
                $payments->map(fn ($p) => [$p->receipt_number, $p->student_name, $p->payment_method, $p->payment_date, $p->amount])
            );
        }

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
                'students.admission_number',
                DB::raw('SUM(sb.balance) as total_balance'),
                DB::raw('SUM(sb.amount) as total_billed'),
                DB::raw('SUM(sb.amount_paid) as total_paid')
            )
            ->whereIn('sb.status', ['unpaid', 'partial'])
            ->groupBy('students.id', 'students.first_name', 'students.last_name', 'students.student_number', 'students.admission_number')
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

        if ($request->format === 'pdf') {
            return $this->exportPdf('reports.finance.debtors', compact('rows'), 'fees-arrears-report.pdf');
        }
        if ($request->format === 'csv') {
            return $this->exportCsv('fees-arrears-report.csv',
                ['Student', 'Student #', 'Form', 'Billed', 'Paid', 'Balance'],
                $rows->map(fn ($r) => [$r->student_name, $r->student_number ?: $r->admission_number, $r->form_name, $r->total_billed, $r->total_paid, $r->total_balance])
            );
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
            ->select('students.id', 'students.first_name', 'students.last_name', 'students.student_number', 'students.admission_number')
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

    /* ── Cashier Reconciliation ───────────────────────────────────────────── */

    public function cashierReconciliation(Request $request)
    {
        $dateFrom = $request->date_from ?? $request->date ?? date('Y-m-d');
        $dateTo   = $request->date_to   ?? $dateFrom;

        $rows = DB::table('finance_payments as fp')
            ->join('users', 'fp.received_by', '=', 'users.id')
            ->select(
                'fp.received_by',
                'users.name as cashier_name',
                'fp.payment_method',
                DB::raw('COUNT(*) as payment_count'),
                DB::raw('SUM(fp.amount) as total_amount')
            )
            ->where('fp.status', 'active')
            ->whereBetween('fp.payment_date', [$dateFrom, $dateTo])
            ->groupBy('fp.received_by', 'users.name', 'fp.payment_method')
            ->orderBy('users.name')
            ->orderBy('fp.payment_method')
            ->get();

        $cashiers = [];
        foreach ($rows as $row) {
            $key = $row->received_by;
            if (!isset($cashiers[$key])) {
                $cashiers[$key] = (object) [
                    'received_by'   => $row->received_by,
                    'cashier_name'  => $row->cashier_name,
                    'by_method'     => [],
                    'payment_count' => 0,
                    'total_amount'  => 0,
                ];
            }
            $cashiers[$key]->by_method[$row->payment_method] = (float) $row->total_amount;
            $cashiers[$key]->payment_count += (int) $row->payment_count;
            $cashiers[$key]->total_amount  += (float) $row->total_amount;
        }

        $cashiers = array_values($cashiers);
        $grandTotal = array_sum(array_map(fn ($c) => $c->total_amount, $cashiers));

        if ($request->format === 'pdf') {
            return $this->exportPdf('reports.finance.cashier-reconciliation',
                compact('cashiers', 'grandTotal', 'dateFrom', 'dateTo'),
                "cashier-reconciliation-{$dateFrom}.pdf");
        }
        if ($request->format === 'csv') {
            $csvRows = [];
            foreach ($cashiers as $c) {
                foreach ($c->by_method as $method => $amount) {
                    $csvRows[] = [$c->cashier_name, $method, $amount];
                }
            }
            return $this->exportCsv("cashier-reconciliation-{$dateFrom}.csv", ['Cashier', 'Method', 'Amount'], $csvRows);
        }

        return response()->json([
            'date_from'   => $dateFrom,
            'date_to'     => $dateTo,
            'cashiers'    => $cashiers,
            'grand_total' => $grandTotal,
        ]);
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

        // §2.3 formula: Opening Balance + Current Charges + Other Charges − Payments = Outstanding Balance.
        // "Current Charges" and "Other Charges" are both fee categories billed within the term, already
        // combined in current_term_charges — the spec doesn't require them tracked as separate figures.
        $termId = $request->term_id ?: DB::table('terms')->where('is_current', true)->value('id');
        $yearId = $request->academic_year_id ?: DB::table('terms')->where('id', $termId)->value('academic_year_id');
        $accountSummary = ($termId && $yearId) ? FeeAccountService::accountSummary($studentId, (int) $yearId, (int) $termId) : null;

        if ($request->format === 'pdf') {
            return $this->exportPdf('reports.finance.statement',
                ['student' => $student, 'transactions' => $transactions, 'accountSummary' => $accountSummary],
                "statement-{$student->admission_number}.pdf");
        }
        if ($request->format === 'csv') {
            return $this->exportCsv("statement-{$student->admission_number}.csv",
                ['Date', 'Description', 'Type', 'Debit', 'Credit', 'Balance'],
                $transactions->map(fn ($t) => [$t->created_at, $t->description, $t->transaction_type, $t->debit, $t->credit, $t->balance_after])
            );
        }

        return response()->json([
            'student'         => $student,
            'transactions'    => $transactions,
            'summary'         => ['total_billed' => $totalBilled, 'total_paid' => $totalPaid, 'total_balance' => $totalBalance],
            'account_summary' => $accountSummary,
        ]);
    }
}
