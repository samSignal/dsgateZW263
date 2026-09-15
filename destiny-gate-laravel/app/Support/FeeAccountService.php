<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Implements the Learner Fee Account formula from the Fee & Uniform Content
 * Specification §2.3: Opening Balance + Current Charges + Other Charges − Payments = Outstanding Balance.
 *
 * Opening Balance and Outstanding Balance are read as point-in-time snapshots off
 * student_account_transactions.balance_after (a running ledger already maintained by
 * every bill/payment write) rather than re-summed live from student_bills — that keeps
 * the figures correct even when a payment made "this term" gets manually allocated to
 * an older bill from a prior term (re-summing live balances split by term would double
 * count that reduction).
 */
class FeeAccountService
{
    /**
     * The account's running balance as of the start of the given term — i.e. the
     * balance_after of the last ledger entry belonging to a term that started earlier.
     * Zero if the student has no ledger history before this term.
     */
    public static function openingBalance(int $studentId, int $termId): float
    {
        $term = DB::table('terms')->where('id', $termId)->first();
        if (!$term) return 0.0;

        $last = DB::table('student_account_transactions as t')
            ->join('terms as tm', 't.term_id', '=', 'tm.id')
            ->where('t.student_id', $studentId)
            ->where('tm.start_date', '<', $term->start_date)
            ->orderByDesc('t.created_at')
            ->orderByDesc('t.id')
            ->value('t.balance_after');

        return (float) ($last ?? 0.0);
    }

    /** Gross amount billed for exactly this academic year + term (all fee categories combined). */
    public static function currentTermCharges(int $studentId, int $academicYearId, int $termId): float
    {
        return (float) DB::table('student_bills')
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->where('status', '!=', 'cancelled')
            ->sum('amount');
    }

    /** Total recorded (non-reversed) payments captured during this academic year + term. */
    public static function paymentsThisTerm(int $studentId, int $academicYearId, int $termId): float
    {
        return (float) DB::table('finance_payments')
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->where('status', 'active')
            ->sum('amount');
    }

    /**
     * The account's current running balance — the balance_after of its most recent
     * ledger entry, or 0 if the student has no ledger history at all. This is the
     * single source of truth for "outstanding balance," and unlike summing
     * student_bills.balance (which floors each bill at zero), it can go **negative**:
     * a student who has paid more than they currently owe carries that as a credit
     * here, which then nets against whatever gets billed in a future term.
     */
    public static function currentLedgerBalance(int $studentId): float
    {
        $last = DB::table('student_account_transactions')
            ->where('student_id', $studentId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->value('balance_after');

        return (float) ($last ?? 0.0);
    }

    /** Authoritative current outstanding balance — negative means the student is in credit. */
    public static function outstandingBalance(int $studentId): float
    {
        return self::currentLedgerBalance($studentId);
    }

    /**
     * Whether this student has ever been billed at all. A balance of 0 is ambiguous on its
     * own — it means "fully paid" for a billed student but "never billed yet" for one who
     * isn't. Callers displaying a balance as "Fully Paid"/"clear" should check this first.
     */
    public static function hasAnyBills(int $studentId): bool
    {
        return DB::table('student_bills')
            ->where('student_id', $studentId)
            ->where('status', '!=', 'cancelled')
            ->exists();
    }

    /** Full §2.3 breakdown for a student's account as of a given term. */
    public static function accountSummary(int $studentId, int $academicYearId, int $termId): array
    {
        return [
            'opening_balance'       => self::openingBalance($studentId, $termId),
            'current_term_charges'  => self::currentTermCharges($studentId, $academicYearId, $termId),
            'payments_this_term'    => self::paymentsThisTerm($studentId, $academicYearId, $termId),
            'outstanding_balance'   => self::outstandingBalance($studentId),
        ];
    }

    /**
     * How much fee credit a student can actually spend right now (0 if they owe money
     * or are exactly settled). This is what the Shop module offers as "Account Credit"
     * — the fee side and the shop side share one account, per the spec's integration
     * rule that fees and uniforms stay separate ledgers but draw on the same balance.
     */
    public static function availableCredit(int $studentId): float
    {
        return max(0.0, -self::currentLedgerBalance($studentId));
    }

    /**
     * Draws down a student's fee credit to pay for something outside the Finance module
     * (currently: a Shop purchase) — records a debit on the same ledger a Finance payment
     * would, so the credit can't be spent twice and the Fee account stays authoritative.
     * Caller is responsible for checking availableCredit() first and for wrapping this in
     * the same DB transaction as the external side's own bookkeeping.
     */
    public static function consumeCredit(int $studentId, float $amount, string $referenceType, int $referenceId, string $description): void
    {
        $term = DB::table('terms')->where('is_current', true)->first()
            ?? DB::table('terms')->orderByDesc('start_date')->first();

        DB::table('student_account_transactions')->insert([
            'student_id'       => $studentId,
            'academic_year_id' => $term->academic_year_id ?? null,
            'term_id'          => $term->id ?? null,
            'transaction_type' => 'adjustment',
            'reference_type'   => $referenceType,
            'reference_id'     => $referenceId,
            'description'      => $description,
            'debit'            => $amount,
            'credit'           => 0,
            'balance_after'    => self::currentLedgerBalance($studentId) + $amount,
            'created_by'       => Auth::id(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }

    /** Inverse of consumeCredit() — restores credit that was drawn down, e.g. when a credit-funded purchase is cancelled. */
    public static function restoreCredit(int $studentId, float $amount, string $referenceType, int $referenceId, string $description): void
    {
        $term = DB::table('terms')->where('is_current', true)->first()
            ?? DB::table('terms')->orderByDesc('start_date')->first();

        DB::table('student_account_transactions')->insert([
            'student_id'       => $studentId,
            'academic_year_id' => $term->academic_year_id ?? null,
            'term_id'          => $term->id ?? null,
            'transaction_type' => 'adjustment',
            'reference_type'   => $referenceType,
            'reference_id'     => $referenceId,
            'description'      => $description,
            'debit'            => 0,
            'credit'           => $amount,
            'balance_after'    => self::currentLedgerBalance($studentId) - $amount,
            'created_by'       => Auth::id(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }
}
