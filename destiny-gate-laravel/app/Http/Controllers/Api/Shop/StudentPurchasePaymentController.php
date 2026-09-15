<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Support\FeeAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentPurchasePaymentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'student_purchase_id' => 'required|exists:student_purchases,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,ecocash,bank_transfer,swipe,online,other,account_credit',
            'reference_number' => 'nullable|string|max:100',
            'payment_date' => 'required|date',
        ]);

        DB::beginTransaction();
        try {
            $purchase = DB::table('student_purchases')->where('id', $data['student_purchase_id'])->lockForUpdate()->first();
            if ($purchase->status === 'cancelled') {
                DB::rollBack();
                return response()->json(['message' => 'Cancelled purchases cannot receive payments.'], 422);
            }
            if ((float) $data['amount'] > (float) $purchase->balance) {
                DB::rollBack();
                return response()->json(['message' => 'Payment cannot exceed purchase balance.'], 422);
            }

            if ($data['payment_method'] === 'account_credit') {
                $available = FeeAccountService::availableCredit($purchase->student_id);
                if ((float) $data['amount'] > $available + 0.01) {
                    DB::rollBack();
                    return response()->json(['message' => "Only \${$available} of fee credit is available for this student."], 422);
                }
                FeeAccountService::consumeCredit(
                    $purchase->student_id, (float) $data['amount'],
                    'student_purchases', $purchase->id,
                    "Fee credit applied to Shop purchase {$purchase->purchase_number}"
                );
            }

            DB::table('student_purchase_payments')->insert([
                'student_purchase_id' => $purchase->id,
                'finance_payment_id' => null,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'received_by' => Auth::id(),
                'payment_date' => $data['payment_date'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $paid = (float) $purchase->amount_paid + (float) $data['amount'];
            $balance = max(0, (float) $purchase->total_amount - $paid);
            $status = $balance <= 0 ? 'paid' : 'partial';

            DB::table('student_purchases')->where('id', $purchase->id)->update([
                'amount_paid' => $paid,
                'balance' => $balance,
                'status' => $status,
                'payment_status' => $status,
                'updated_at' => now(),
            ]);

            DB::commit();
            return response()->json(['message' => 'Payment recorded.'], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function purchasePayments(int $purchaseId)
    {
        $payments = DB::table('student_purchase_payments as spp')
            ->leftJoin('users as u', 'spp.received_by', '=', 'u.id')
            ->select('spp.*', 'u.name as received_by_name')
            ->where('spp.student_purchase_id', $purchaseId)
            ->orderByDesc('spp.payment_date')
            ->get();

        return response()->json($payments);
    }
}
