<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Format: W0{YY}{NNNN}{L}
 * Example: W0261234A — W, literal 0, last 2 digits of year, 4-digit sequence
 * (per year, based on existing admission count), one random trailing letter.
 * The single source of truth for this format — used by every path that
 * creates a Student, so the manual "Add Student" form and the admissions
 * enrollment flow can never drift apart on format again.
 */
class StudentNumberGenerator
{
    public static function generate(string $admissionDate): string
    {
        $year = date('Y', strtotime($admissionDate));
        $yy = substr($year, -2);

        $attempts = 0;
        do {
            $attempts++;
            $count = DB::table('students')->whereYear('admission_date', $year)->count();
            $seq = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            $letter = chr(random_int(65, 90));
            $number = "W0{$yy}{$seq}{$letter}";

            $taken = DB::table('students')
                ->where('student_number', $number)
                ->orWhere('admission_number', $number)
                ->exists();
        } while ($taken && $attempts < 5);

        return $number;
    }
}
