<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Format: {PREFIX}{YY}{NNNN}{L}
 * Example: D0261234A — D0, a 2-digit year code, 4 random digits, one random trailing
 * letter. Form 5/6 (A-Level) use "DI" instead of "D0" as the prefix — same year-code and
 * suffix mechanics, just a different, easy-to-spot marker for the O-Level/A-Level split
 * (e.g. DI265831Q) — see PREFIX_BY_FORM_LEVEL below.
 *
 * The digits and letter are both drawn from random_int() (a CSPRNG), never rand()/mt_rand(),
 * since this is a security-sensitive identifier (it doubles as the student's login
 * username). Collisions are checked against the database and retried, and student_number/
 * admission_number both carry a UNIQUE constraint at the DB level as a second line of
 * defense against a race between two concurrent enrollments.
 *
 * Two ways to land on the 2-digit year code:
 *  - generate(): for any student going through this system's own enrollment process for
 *    the first time (new admissions, today or in any future year) — the code comes from
 *    their actual admission_date, calculated automatically, no lookup table to maintain.
 *  - generateForImport(): for backfilling students who already attend the school from
 *    before it adopted this system (see StudentApiController::bulkImport()) — the code
 *    comes from IMPORT_FORM_YEAR_CODE below instead, since asking every school office
 *    staff member to know each legacy student's exact admission date is unreasonable
 *    busywork when the current form already tells you which intake they belong to.
 *
 * The single source of truth for this format — used by every path that creates a
 * Student, so the manual "Add Student" form, the admissions enrollment flow, and bulk
 * import can never drift apart on format again.
 */
class StudentNumberGenerator
{
    /**
     * One-time snapshot for backfilling the school's students as they stood in 2026 —
     * not a formula that keeps working on its own in future years. Form 1 and Form 5 are
     * this school's two intake points (O-Level and A-Level respectively), so both read
     * "this year" (26); Form 2/6 are one year into their track (25); Form 3 is two years
     * in (24); Form 4 is three years in (23). If the school ever needs to bulk-import
     * another batch of pre-existing students in some later year, this table needs a
     * fresh set of codes for that year — there's no way to derive it automatically,
     * because "years since intake" isn't recoverable from the form alone once time moves on.
     */
    private const IMPORT_FORM_YEAR_CODE = [
        1 => '26',
        2 => '25',
        3 => '24',
        4 => '23',
        5 => '26',
        6 => '25',
    ];

    /** A-Level forms get a visually distinct prefix from O-Level's "D0" — everything else
     *  about the number (year code, random digits, letter) works exactly the same way. */
    private const PREFIX_BY_FORM_LEVEL = [
        5 => 'DI',
        6 => 'DI',
    ];

    public static function generate(string $admissionDate): string
    {
        $year = date('Y', strtotime($admissionDate));
        return self::buildAndCheck('D0', substr($year, -2));
    }

    /** @param int $formLevel forms.level (1-6) of the student being imported */
    public static function generateForImport(int $formLevel): string
    {
        $yy = self::IMPORT_FORM_YEAR_CODE[$formLevel] ?? substr(date('Y'), -2);
        $prefix = self::PREFIX_BY_FORM_LEVEL[$formLevel] ?? 'D0';
        return self::buildAndCheck($prefix, $yy);
    }

    private static function buildAndCheck(string $prefix, string $yy): string
    {
        $attempts = 0;
        do {
            $attempts++;
            $seq = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $letter = chr(random_int(65, 90));
            $number = "{$prefix}{$yy}{$seq}{$letter}";

            $taken = DB::table('students')
                ->where('student_number', $number)
                ->orWhere('admission_number', $number)
                ->exists();
        } while ($taken && $attempts < 20);

        return $number;
    }
}
