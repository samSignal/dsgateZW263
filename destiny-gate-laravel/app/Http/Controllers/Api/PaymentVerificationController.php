<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

/**
 * Public, unauthenticated payment verification — deliberately outside auth:sanctum so a
 * parent can scan a receipt's QR code (or type the reference in by hand) without logging in.
 * Covers both Finance (fee payments) and Shop (uniform/tuck-shop purchases) — one front door
 * for either kind of receipt, since a parent scanning a QR shouldn't need to know which
 * module issued it.
 *
 * Trust model: the reference itself is the capability. It's a system-generated value with a
 * random component (see PaymentReferenceGenerator) that isn't derivable from a date/time or
 * student info alone, so knowing it is treated as proof you were legitimately handed a real
 * receipt — the whole point is that a reference typed off a screenshot or forwarded WhatsApp
 * message either matches a real, stored row or it doesn't. This endpoint is the only source
 * of truth for that; nothing about a payment or purchase should ever be trusted from what's
 * printed on a document someone else supplies.
 */
class PaymentVerificationController extends Controller
{
    public function verify(string $reference)
    {
        $reference = strtoupper(trim($reference));

        $payment = DB::table('finance_payments as fp')
            ->join('students as s', 'fp.student_id', '=', 's.id')
            ->leftJoin('users as u', 'fp.received_by', '=', 'u.id')
            ->select('fp.*', 's.first_name', 's.last_name', 's.admission_number', 'u.name as received_by_name')
            ->where('fp.payment_reference', $reference)
            ->orWhere('fp.receipt_number', $reference)
            ->first();

        if ($payment) {
            DB::table('finance_payments')->where('id', $payment->id)->update(['verified_at' => now()]);

            return response()->json([
                'verified'          => true,
                'type'              => 'fee_payment',
                'status'            => $payment->status === 'active' ? 'CONFIRMED' : 'REVERSED',
                'school_name'       => 'DestinyGate Institute',
                'student_name'      => trim("{$payment->first_name} {$payment->last_name}"),
                'admission_number'  => $payment->admission_number,
                'amount'            => (float) $payment->amount,
                'payment_method'    => $payment->payment_method,
                'payment_date'      => $payment->payment_date,
                'document_number'   => $payment->receipt_number,
                'document_label'    => 'Receipt',
                'reference'         => $payment->payment_reference,
                'processed_by'      => $payment->received_by_name,
            ]);
        }

        $purchase = DB::table('student_purchases as sp')
            ->join('students as s', 'sp.student_id', '=', 's.id')
            ->leftJoin('users as u', 'sp.recorded_by', '=', 'u.id')
            ->select('sp.*', 's.first_name', 's.last_name', 's.admission_number', 'u.name as recorded_by_name')
            ->where('sp.purchase_reference', $reference)
            ->orWhere('sp.purchase_number', $reference)
            ->first();

        if ($purchase) {
            DB::table('student_purchases')->where('id', $purchase->id)->update(['verified_at' => now()]);

            $itemCount = DB::table('student_purchase_items')->where('student_purchase_id', $purchase->id)->sum('quantity');

            return response()->json([
                'verified'          => true,
                'type'              => 'shop_purchase',
                'status'            => $purchase->status === 'cancelled' ? 'CANCELLED' : 'CONFIRMED',
                'school_name'       => 'DestinyGate Institute',
                'student_name'      => trim("{$purchase->first_name} {$purchase->last_name}"),
                'admission_number'  => $purchase->admission_number,
                'amount'            => (float) $purchase->total_amount,
                'amount_paid'       => (float) $purchase->amount_paid,
                'balance'           => (float) $purchase->balance,
                'item_count'        => (int) $itemCount,
                'payment_date'      => $purchase->purchase_date,
                'document_number'   => $purchase->purchase_number,
                'document_label'    => 'Purchase',
                'reference'         => $purchase->purchase_reference,
                'processed_by'      => $purchase->recorded_by_name,
            ]);
        }

        return response()->json([
            'verified' => false,
            'message'  => 'No payment or purchase found matching this reference. If you received this from a screenshot or forwarded message, do not trust it without independent verification here.',
        ], 404);
    }
}
