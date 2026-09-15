<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Zimbabwe's O-Level/A-Level structure, as a map rather than a rule engine: Form 1-4 is
 * one continuous 12-term track (3 terms/year × 4 forms) that completes at Form 4 Term 3
 * — O-Level graduation. Form 5-6 is a separate 6-term track a student enrolls into afresh
 * (not a continuation of Form 4), completing at Form 6 Term 3 — A-Level graduation.
 *
 * This is deliberately a straight-line *expected* roadmap computed from the student's
 * current form and the school's current term — not a historical log (student_enrollments
 * already exists for that, populated by the separate results-based StreamNativeProgressionEngine
 * as marks come in) and not a repeat/holdback-aware decision engine (same engine's job).
 * It answers one question for the student card: "where are they now, and how far to
 * graduation" — useful from day one, before a single mark has ever been entered.
 */
class EnrollmentRoadmap
{
    private const O_LEVEL_FORMS = [1, 2, 3, 4];
    private const A_LEVEL_FORMS = [5, 6];
    private const TERMS_PER_YEAR = 3;

    /** "Term 2" -> 2. Falls back to 1 if the term name doesn't end in a recognizable number. */
    private static function termNumber(string $termName): int
    {
        return preg_match('/(\d+)\s*$/', $termName, $m) ? max(1, min(self::TERMS_PER_YEAR, (int) $m[1])) : 1;
    }

    public static function forStudent(int $studentId): ?array
    {
        $resolved = StudentStreamResolver::resolveByStudentId($studentId);
        $formLevel = $resolved?->form_id ? (int) DB::table('forms')->where('id', $resolved->form_id)->value('level') : null;
        if (!$formLevel) return null;

        $track = in_array($formLevel, self::O_LEVEL_FORMS, true) ? 'o_level'
            : (in_array($formLevel, self::A_LEVEL_FORMS, true) ? 'a_level' : null);
        if (!$track) return null;

        $forms = $track === 'o_level' ? self::O_LEVEL_FORMS : self::A_LEVEL_FORMS;
        $formNames = DB::table('forms')->whereIn('level', $forms)->pluck('name', 'level');

        $currentTerm = DB::table('terms')->where('is_current', true)->first();
        $currentTermNumber = $currentTerm ? self::termNumber($currentTerm->name) : 1;

        // Every form takes exactly one calendar year (3 terms), so a stage's expected
        // year is just the current year shifted by however many forms away it is —
        // one year earlier per form below the student's current one, one year later per
        // form above it. Falls back to today's year if the current academic year's name
        // isn't a plain 4-digit year for some reason.
        $currentYearName = $currentTerm ? DB::table('academic_years')->where('id', $currentTerm->academic_year_id)->value('name') : null;
        $currentYear = ($currentYearName && preg_match('/^\d{4}$/', $currentYearName)) ? (int) $currentYearName : (int) date('Y');

        $stages = [];
        $currentIndex = null;
        $index = 0;
        foreach ($forms as $formIdx => $level) {
            for ($termNumber = 1; $termNumber <= self::TERMS_PER_YEAR; $termNumber++) {
                $isLastStage = ($formIdx === count($forms) - 1) && $termNumber === self::TERMS_PER_YEAR;
                $isCurrent = $level === $formLevel && $termNumber === $currentTermNumber;
                if ($isCurrent) $currentIndex = $index;

                $stages[] = [
                    'index'         => $index,
                    'form_level'    => $level,
                    'form_name'     => $formNames[$level] ?? "Form {$level}",
                    'term_number'   => $termNumber,
                    'expected_year' => $currentYear + ($level - $formLevel),
                    'is_graduation' => $isLastStage,
                    'status'        => 'upcoming', // corrected to completed/current below once currentIndex is known
                ];
                $index++;
            }
        }

        // $currentIndex is only known once the whole loop above has run (a later stage
        // might match before an earlier one is reached, if the student's own form is
        // out of step with the school's current term for any reason) — mark completed/
        // current/upcoming in a second pass now that it's settled.
        foreach ($stages as &$stage) {
            $stage['status'] = $currentIndex === null ? 'upcoming'
                : ($stage['index'] < $currentIndex ? 'completed' : ($stage['index'] === $currentIndex ? 'current' : 'upcoming'));
        }
        unset($stage);

        $lastStage = end($stages);
        $trackLabel = $track === 'o_level' ? 'O-Level' : 'A-Level';

        return [
            'track'               => $track,
            'track_label'         => $trackLabel,
            'current_form_name'   => $formNames[$formLevel] ?? "Form {$formLevel}",
            'current_term_name'   => $currentTerm->name ?? null,
            'current_index'       => $currentIndex,
            'total_stages'        => count($stages),
            'graduation_label'    => "{$lastStage['form_name']}, Term {$lastStage['term_number']}, {$lastStage['expected_year']} ({$trackLabel} Graduation)",
            'is_at_graduation'    => $currentIndex !== null && $currentIndex === $lastStage['index'],
            'stages'              => $stages,
        ];
    }
}
