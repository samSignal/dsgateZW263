<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentFee;
use App\Models\Payment;
use App\Models\FeeStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BursarApiController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'total_collected'   => Payment::whereYear('payment_date', date('Y'))->sum('amount'),
            'total_outstanding' => StudentFee::where('status', '!=', 'paid')->sum('balance'),
            'paid_in_full'      => StudentFee::where('status', 'paid')->count(),
            'debtors_count'     => StudentFee::whereIn('status', ['overdue','partial'])->distinct('student_id')->count('student_id'),
            'recent_payments'   => Payment::with(['student', 'recordedBy'])->latest()->take(10)->get(),
        ]);
    }

    public function fees()
    {
        $fees = StudentFee::with('student.schoolClass')->latest()->paginate(20);
        return response()->json($fees);
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
            $n = Payment::count() + 1;
            Payment::create([
                ...$data,
                'payment_number' => 'PAY-' . date('Ymd') . '-' . str_pad($n, 4, '0', STR_PAD_LEFT),
                'receipt_number' => 'RCP-' . date('Ymd') . '-' . str_pad($n, 4, '0', STR_PAD_LEFT),
                'recorded_by'    => auth()->id(),
            ]);
            $fee = StudentFee::find($data['student_fee_id']);
            $paid = $fee->amount_paid + $data['amount'];
            $bal  = max(0, $fee->amount - $paid);
            $fee->update(['amount_paid' => $paid, 'balance' => $bal, 'status' => $bal <= 0 ? 'paid' : 'partial']);
        });

        return response()->json(['message' => 'Payment recorded.']);
    }

    public function feeStructures()
    {
        return response()->json(FeeStructure::latest()->paginate(20));
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
        $fs = FeeStructure::create($data);
        return response()->json(['message' => 'Fee structure created.', 'fee_structure' => $fs], 201);
    }

    public function debtorsList()
    {
        $debtors = StudentFee::with('student.schoolClass')
            ->whereIn('status', ['overdue', 'partial'])
            ->get();
        return response()->json($debtors);
    }

    public function paidStudents()
    {
        $paid = StudentFee::with('student.schoolClass')->where('status', 'paid')->paginate(20);
        return response()->json($paid);
    }

    public function paymentHistory()
    {
        $payments = Payment::with(['student', 'recordedBy'])->latest()->paginate(20);
        return response()->json($payments);
    }
}
