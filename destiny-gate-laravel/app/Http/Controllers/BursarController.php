<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentFee;
use App\Models\Payment;
use App\Models\FeeStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BursarController extends Controller
{
    public function dashboard()
    {
        $currentYear = date('Y') . '/' . (date('Y') + 1);

        $stats = [
            'total_collected'  => Payment::whereYear('payment_date', date('Y'))->sum('amount'),
            'total_outstanding'=> StudentFee::where('status', '!=', 'paid')->sum('balance'),
            'paid_in_full'     => StudentFee::where('status', 'paid')->count(),
            'debtors_count'    => StudentFee::where('status', 'overdue')->distinct('student_id')->count('student_id'),
        ];

        $recentPayments = Payment::with(['student', 'recordedBy'])
            ->latest()
            ->take(10)
            ->get();

        return view('bursar.dashboard', compact('stats', 'recentPayments'));
    }

    public function fees()
    {
        $fees = StudentFee::with('student.schoolClass')
            ->latest()
            ->paginate(20);

        return view('bursar.fees.index', compact('fees'));
    }

    public function recordPayment(Request $request)
    {
        $data = $request->validate([
            'student_id'     => 'required|exists:students,id',
            'student_fee_id' => 'required|exists:student_fees,id',
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,check,online',
            'payment_date'   => 'required|date',
            'notes'          => 'nullable|string',
        ]);

        DB::transaction(function () use ($data) {
            $paymentNumber = 'PAY-' . date('Ymd') . '-' . str_pad(Payment::count() + 1, 4, '0', STR_PAD_LEFT);
            $receiptNumber = 'RCP-' . date('Ymd') . '-' . str_pad(Payment::count() + 1, 4, '0', STR_PAD_LEFT);

            Payment::create([
                ...$data,
                'payment_number' => $paymentNumber,
                'receipt_number' => $receiptNumber,
                'recorded_by'    => auth()->id(),
            ]);

            // Update student fee balance
            $fee = StudentFee::find($data['student_fee_id']);
            $newAmountPaid = $fee->amount_paid + $data['amount'];
            $newBalance    = $fee->amount - $newAmountPaid;

            $status = 'partial';
            if ($newBalance <= 0) {
                $status = 'paid';
                $newBalance = 0;
            }

            $fee->update([
                'amount_paid' => $newAmountPaid,
                'balance'     => $newBalance,
                'status'      => $status,
            ]);
        });

        return back()->with('success', 'Payment recorded successfully.');
    }

    public function feeStructures()
    {
        $structures = FeeStructure::latest()->paginate(20);
        return view('bursar.fee-structures.index', compact('structures'));
    }

    public function storeFeeStructure(Request $request)
    {
        $data = $request->validate([
            'academic_year' => 'required|string|max:20',
            'class_name'    => 'required|string|max:100',
            'term'          => 'required|in:term1,term2,term3',
            'amount'        => 'required|numeric|min:0',
            'due_date'      => 'required|date',
            'description'   => 'nullable|string',
        ]);

        FeeStructure::create($data);

        return back()->with('success', 'Fee structure created.');
    }

    public function debtorsList()
    {
        $debtors = StudentFee::with('student.schoolClass')
            ->where('status', 'overdue')
            ->orWhere('status', 'partial')
            ->get()
            ->groupBy('student_id');

        return view('bursar.reports.debtors', compact('debtors'));
    }

    public function paidStudents()
    {
        $paid = StudentFee::with('student.schoolClass')
            ->where('status', 'paid')
            ->paginate(20);

        return view('bursar.reports.paid', compact('paid'));
    }

    public function paymentHistory()
    {
        $payments = Payment::with(['student', 'recordedBy'])
            ->latest()
            ->paginate(20);

        return view('bursar.reports.payments', compact('payments'));
    }
}
