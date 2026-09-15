<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Generates the verification identifiers used across both Finance and Shop:
 *
 *  - Payment Reference:  DGS-PAY-{YYYYMMDD}-{HHMMSS}-{RANDOM6}  (fee payments)
 *  - Purchase Reference: DGS-SHP-{YYYYMMDD}-{HHMMSS}-{RANDOM6}  (shop purchases)
 *    Transaction identifiers. Each includes a random suffix specifically so it can't be
 *    guessed or reconstructed from a date/time alone — that randomness is what makes a
 *    reference on a screenshot or WhatsApp message worthless to a scammer without it also
 *    being a real, stored row: see PaymentVerificationController, the only source of truth
 *    for whether a reference is genuine, for either kind.
 *
 *  - Receipt Number: DGS-RCP-{YYYY}-{8-digit sequence}
 *    The official fee-payment document number, sequential per year like the fee category
 *    codes and admission numbers elsewhere in the system. Shop purchases keep their existing
 *    PUR-{YYYY}-{seq} document numbering (StudentPurchaseController::nextPurchaseNumber) —
 *    only their verification reference is new.
 *
 * All of the above avoid ambiguous characters (0/O, 1/I) so they read correctly off a
 * printed receipt.
 */
class PaymentReferenceGenerator
{
    private const RANDOM_ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public static function generateReference(?string $at = null): string
    {
        return self::generatePrefixed('DGS-PAY', $at, 'finance_payments', 'payment_reference');
    }

    public static function generatePurchaseReference(?string $at = null): string
    {
        return self::generatePrefixed('DGS-SHP', $at, 'student_purchases', 'purchase_reference');
    }

    private static function generatePrefixed(string $prefix, ?string $at, string $table, string $column): string
    {
        $date = $at ? new \DateTime($at) : now()->toDateTime();
        $datePart = $date->format('Ymd');
        $timePart = $date->format('His');

        $attempts = 0;
        do {
            $attempts++;
            $random = self::randomSuffix(6);
            $reference = "{$prefix}-{$datePart}-{$timePart}-{$random}";
            $taken = DB::table($table)->where($column, $reference)->exists();
        } while ($taken && $attempts < 5);

        return $reference;
    }

    public static function generateReceiptNumber(): string
    {
        $year = date('Y');
        $last = DB::table('finance_payments')
            ->where('receipt_number', 'like', "DGS-RCP-{$year}-%")
            ->orderByDesc('id')->value('receipt_number');
        $seq = $last ? ((int) substr($last, -8)) + 1 : 1;
        return "DGS-RCP-{$year}-" . str_pad((string) $seq, 8, '0', STR_PAD_LEFT);
    }

    private static function randomSuffix(int $length): string
    {
        $alphabet = self::RANDOM_ALPHABET;
        $max = strlen($alphabet) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }
        return $out;
    }
}
