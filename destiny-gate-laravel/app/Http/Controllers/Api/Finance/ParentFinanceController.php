<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Support\StudentStreamResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ParentFinanceController extends Controller
{
    private function getLinkedStudentIds(): array
    {
        return DB::table('guardians')
            ->where('user_id', Auth::id())
            ->pluck('student_id')
            ->toArray();
    }

    public function myChildrenBalances()
    {
        $studentIds = $this->getLinkedStudentIds();
        if (empty($studentIds)) {
            return response()->json(['message' => 'No children linked to your account.', 'children' => []]);
        }

        $children = DB::table('students')
            ->whereIn('students.id', $studentIds)
            ->select('students.id', 'students.first_name', 'students.last_name', 'students.student_number')
            ->get()
            ->map(function ($s) {
                $s->name = trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? ''));
                StudentStreamResolver::attachResolvedFields($s);
                $s->balance = DB::table('student_bills')
                    ->where('student_id', $s->id)
                    ->where('status', '!=', 'cancelled')
                    ->sum('balance');
                return $s;
            });

        return response()->json(['children' => $children]);
    }

    public function childBills(int $studentId, Request $request)
    {
        $this->authorizeChild($studentId);

        $bills = DB::table('student_bills as sb')
            ->join('fee_categories', 'sb.fee_category_id', '=', 'fee_categories.id')
            ->join('terms', 'sb.term_id', '=', 'terms.id')
            ->join('academic_years', 'sb.academic_year_id', '=', 'academic_years.id')
            ->select('sb.*', 'fee_categories.name as category_name', 'terms.name as term_name', 'academic_years.name as academic_year_name')
            ->where('sb.student_id', $studentId)
            ->where('sb.status', '!=', 'cancelled')
            ->orderBy('sb.due_date')
            ->get();

        return response()->json($bills);
    }

    public function childPayments(int $studentId)
    {
        $this->authorizeChild($studentId);

        $payments = DB::table('finance_payments as fp')
            ->join('terms', 'fp.term_id', '=', 'terms.id')
            ->join('academic_years', 'fp.academic_year_id', '=', 'academic_years.id')
            ->join('users', 'fp.received_by', '=', 'users.id')
            ->select('fp.*', 'terms.name as term_name', 'academic_years.name as academic_year_name', 'users.name as received_by_name')
            ->where('fp.student_id', $studentId)
            ->orderByDesc('fp.payment_date')
            ->get();

        return response()->json($payments);
    }

    public function childReceipts(int $studentId)
    {
        $this->authorizeChild($studentId);
        return $this->childPayments($studentId);
    }

    public function childStatement(int $studentId, Request $request)
    {
        $this->authorizeChild($studentId);

        $transactions = DB::table('student_account_transactions')
            ->where('student_id', $studentId)
            ->orderBy('created_at')
            ->get();

        $balance = DB::table('student_bills')
            ->where('student_id', $studentId)
            ->where('status', '!=', 'cancelled')
            ->sum('balance');

        return response()->json(['transactions' => $transactions, 'balance' => $balance]);
    }

    private function authorizeChild(int $studentId): void
    {
        $linked = $this->getLinkedStudentIds();
        abort_if(!in_array($studentId, $linked), 403, 'You are not authorized to view this student\'s records.');
    }
}
