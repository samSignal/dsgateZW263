<?php

namespace Tests\Feature;

use App\Support\StreamNativeProgressionEngine;
use App\Support\StreamNativeResultsEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Phase2BStreamNativeResultsEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_results_aggregation_rankings_progression_and_transcript_work_for_direct_and_legacy_students_with_finance_withheld(): void
    {
        $setup = $this->foundation();
        $this->progressionRule($setup);

        $subjectMath = $this->subject('Mathematics', 'MATH');
        $subjectEng = $this->subject('English', 'ENG');

        $typeTest = $this->assessmentType('Test', 40);
        $typeExam = $this->assessmentType('Exam', 60);

        $directStudent = $this->student(['stream_id' => $setup['stream_id']]);
        $legacyStudent = $this->student(['class_id' => $setup['class_id']]);
        $withheldStudent = $this->student(['stream_id' => $setup['stream_id']]);

        $this->enrollSubjects($directStudent, [$subjectMath, $subjectEng], $setup);
        $this->enrollSubjects($legacyStudent, [$subjectMath, $subjectEng], $setup);
        $this->enrollSubjects($withheldStudent, [$subjectMath, $subjectEng], $setup);

        $a1 = $this->assessment($setup, $subjectMath, $typeTest, 'Math Test', 20, '2026-05-10');
        $a2 = $this->assessment($setup, $subjectMath, $typeExam, 'Math Exam', 100, '2026-05-20');
        $a3 = $this->assessment($setup, $subjectEng, $typeTest, 'Eng Test', 20, '2026-05-11');
        $a4 = $this->assessment($setup, $subjectEng, $typeExam, 'Eng Exam', 100, '2026-05-21');

        $this->mark($a1, $directStudent, 80);
        $this->mark($a2, $directStudent, 70);
        $this->mark($a3, $directStudent, 60);
        $this->mark($a4, $directStudent, 65);

        $this->mark($a1, $legacyStudent, 30);
        $this->mark($a2, $legacyStudent, 40);
        $this->mark($a3, $legacyStudent, 45);
        $this->mark($a4, $legacyStudent, 35);

        $this->mark($a1, $withheldStudent, 90);
        $this->mark($a2, $withheldStudent, 85);
        $this->mark($a3, $withheldStudent, 75);
        $this->mark($a4, $withheldStudent, 80);

        $this->bill($withheldStudent, $setup, 200, 0);

        StreamNativeResultsEngine::recomputeStudentTerm($directStudent, $setup['academic_year_id'], $setup['term_id']);
        StreamNativeResultsEngine::recomputeStudentTerm($legacyStudent, $setup['academic_year_id'], $setup['term_id']);
        StreamNativeResultsEngine::recomputeStudentTerm($withheldStudent, $setup['academic_year_id'], $setup['term_id']);

        $this->assertSame(3, (int) DB::table('results_aggregates')->count());
        $this->assertSame(3, (int) DB::table('student_enrollments')->where('academic_year_id', $setup['academic_year_id'])->where('term_id', $setup['term_id'])->count());

        $legacyAgg = DB::table('results_aggregates')->where('student_id', $legacyStudent)->first();
        $this->assertSame($setup['stream_id'], (int) $legacyAgg->stream_id);
        $this->assertSame($setup['form_id'], (int) $legacyAgg->form_id);
        $this->assertSame($setup['category_id'], (int) $legacyAgg->category_id);

        $withheldAgg = DB::table('results_aggregates')->where('student_id', $withheldStudent)->first();
        $this->assertTrue((bool) $withheldAgg->is_withheld);

        $this->assertSame(3 * 2, (int) DB::table('transcript_subject_history')->count());
        $this->assertSame(3, (int) DB::table('transcript_year_aggregates')->count());
        $this->assertSame(3, (int) DB::table('graduation_readiness')->count());

        StreamNativeResultsEngine::recomputeGroupAggregates($setup['academic_year_id'], $setup['term_id'], 'stream', $setup['stream_id']);
        $groupRow = DB::table('results_group_aggregates')->where('group_type', 'stream')->where('group_id', $setup['stream_id'])->first();
        $this->assertNotNull($groupRow);
        $this->assertSame(2, (int) $groupRow->student_count);

        StreamNativeResultsEngine::recomputeRankings($setup['academic_year_id'], $setup['term_id'], 'stream', $setup['stream_id']);
        $rankRows = DB::table('results_rankings')->where('ranking_type', 'stream')->where('ranking_id', $setup['stream_id'])->get();
        $this->assertSame(2, (int) $rankRows->count());
        $this->assertFalse($rankRows->pluck('student_id')->contains($withheldStudent));

        $directDecision = StreamNativeProgressionEngine::decide($directStudent, $setup['academic_year_id'], $setup['term_id']);
        $legacyDecision = StreamNativeProgressionEngine::decide($legacyStudent, $setup['academic_year_id'], $setup['term_id']);
        $withheldDecision = StreamNativeProgressionEngine::decide($withheldStudent, $setup['academic_year_id'], $setup['term_id']);

        $this->assertSame('promote', $directDecision['decision']);
        $this->assertContains($legacyDecision['decision'], ['repeat', 'supplementary', 'incomplete']);
        $this->assertSame('withheld', $withheldDecision['decision']);

        $nextTermId = $this->term($setup['academic_year_id'], 'Term 2', false);
        StreamNativeProgressionEngine::applyDecision(
            $directStudent,
            $setup['academic_year_id'],
            $setup['term_id'],
            $setup['academic_year_id'],
            $nextTermId,
            $setup['stream_id'],
            'promote'
        );

        $enrollment = DB::table('student_enrollments')
            ->where('student_id', $directStudent)
            ->where('academic_year_id', $setup['academic_year_id'])
            ->where('term_id', $nextTermId)
            ->first();
        $this->assertNotNull($enrollment);
        $this->assertSame($setup['stream_id'], (int) $enrollment->promoted_to_stream_id);
        $this->assertSame('enrolled', $enrollment->enrollment_status);
    }

    private function foundation(): array
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'role' => 'admin',
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('grading_scales')->insert([
            ['name' => 'Default', 'min_percentage' => 0, 'max_percentage' => 39.99, 'grade' => 'U', 'remark' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Default', 'min_percentage' => 40, 'max_percentage' => 49.99, 'grade' => 'D', 'remark' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Default', 'min_percentage' => 50, 'max_percentage' => 59.99, 'grade' => 'C', 'remark' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Default', 'min_percentage' => 60, 'max_percentage' => 69.99, 'grade' => 'B', 'remark' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Default', 'min_percentage' => 70, 'max_percentage' => 100, 'grade' => 'A', 'remark' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Sciences',
            'code' => 'SCI',
            'description' => 'Science pathway.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $formId = DB::table('forms')->insertGetId([
            'name' => 'Form 5',
            'level' => 5,
            'description' => 'Advanced Level',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $streamId = DB::table('streams')->insertGetId([
            'form_id' => $formId,
            'category_id' => $categoryId,
            'name' => 'Sciences',
            'capacity' => 40,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $academicYearId = DB::table('academic_years')->insertGetId([
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $termId = $this->term($academicYearId, 'Term 1', true);
        $classId = DB::table('classes')->insertGetId([
            'class_name' => 'Form 5',
            'stream' => 'Sciences',
            'academic_year' => '2026',
            'capacity' => 40,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'user_id' => $userId,
            'category_id' => $categoryId,
            'form_id' => $formId,
            'stream_id' => $streamId,
            'academic_year_id' => $academicYearId,
            'term_id' => $termId,
            'class_id' => $classId,
        ];
    }

    private function term(int $academicYearId, string $name, bool $isCurrent): int
    {
        return DB::table('terms')->insertGetId([
            'academic_year_id' => $academicYearId,
            'name' => $name,
            'start_date' => '2026-01-01',
            'end_date' => '2026-04-01',
            'is_current' => $isCurrent,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function progressionRule(array $setup): void
    {
        DB::table('progression_rules')->insert([
            'academic_year_id' => $setup['academic_year_id'],
            'form_id' => $setup['form_id'],
            'category_id' => $setup['category_id'],
            'minimum_pass_mark' => 50,
            'promotion_min_average' => 60,
            'promotion_max_failed_subjects' => 1,
            'repeat_below_average' => 40,
            'supplementary_below_average' => 50,
            'required_subject_ids_json' => null,
            'withhold_results_on_balance' => true,
            'withhold_balance_threshold' => 0,
            'gpa_scale_json' => null,
            'created_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assessmentType(string $name, float $weight): int
    {
        return DB::table('assessment_types')->insertGetId([
            'name' => $name,
            'weight_percentage' => $weight,
            'description' => $name,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function subject(string $name, string $code): int
    {
        return DB::table('subjects')->insertGetId([
            'subject_group_id' => null,
            'name' => $name,
            'code' => $code,
            'pass_mark' => 50,
            'is_compulsory' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function student(array $overrides = []): int
    {
        static $count = 0;
        $count++;
        return DB::table('students')->insertGetId([
            'admission_number' => 'DGI-P2B-' . $count,
            'student_number' => 'STU-P2B-' . $count,
            'first_name' => 'Test',
            'last_name' => 'Student ' . $count,
            'admission_date' => '2026-01-15',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
            ...$overrides,
        ]);
    }

    private function enrollSubjects(int $studentId, array $subjectIds, array $setup): void
    {
        foreach ($subjectIds as $subjectId) {
            $streamSubjectId = (int) DB::table('stream_subjects')
                ->where('academic_year_id', $setup['academic_year_id'])
                ->where('term_id', $setup['term_id'])
                ->where('form_id', $setup['form_id'])
                ->where('stream_id', $setup['stream_id'])
                ->where('subject_id', $subjectId)
                ->value('id');
            if (!$streamSubjectId) {
                $streamSubjectId = (int) DB::table('stream_subjects')->insertGetId([
                    'academic_year_id' => $setup['academic_year_id'],
                    'term_id' => $setup['term_id'],
                    'form_id' => $setup['form_id'],
                    'stream_id' => $setup['stream_id'],
                    'subject_id' => $subjectId,
                    'is_compulsory' => true,
                    'created_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table('student_subjects')->insert([
                'student_id' => $studentId,
                'stream_subject_id' => $streamSubjectId,
                'subject_id' => $subjectId,
                'academic_year_id' => $setup['academic_year_id'],
                'term_id' => $setup['term_id'],
                'is_compulsory' => true,
                'enrollment_status' => 'active',
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function assessment(array $setup, int $subjectId, int $typeId, string $title, float $totalMarks, string $date): int
    {
        return DB::table('assessments')->insertGetId([
            'assessment_number' => 'A-P2B-' . uniqid(),
            'academic_year_id' => $setup['academic_year_id'],
            'term_id' => $setup['term_id'],
            'stream_id' => $setup['stream_id'],
            'subject_id' => $subjectId,
            'assessment_type_id' => $typeId,
            'title' => $title,
            'total_marks' => $totalMarks,
            'assessment_date' => $date,
            'created_by' => $setup['user_id'],
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function mark(int $assessmentId, int $studentId, float $percentage): void
    {
        DB::table('assessment_marks')->insert([
            'assessment_id' => $assessmentId,
            'student_id' => $studentId,
            'mark_obtained' => $percentage,
            'percentage' => $percentage,
            'grade' => null,
            'teacher_comment' => null,
            'entered_by' => null,
            'entered_at' => now(),
            'status' => 'entered',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function bill(int $studentId, array $setup, float $amount, float $paid): void
    {
        $feeCategoryId = (int) DB::table('fee_categories')->value('id');
        if (!$feeCategoryId) {
            $feeCategoryId = (int) DB::table('fee_categories')->insertGetId([
                'name' => 'Tuition',
                'description' => 'Tuition',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        DB::table('student_bills')->insert([
            'bill_number' => 'BILL-P2B-' . uniqid(),
            'student_id' => $studentId,
            'academic_year_id' => $setup['academic_year_id'],
            'term_id' => $setup['term_id'],
            'fee_structure_id' => null,
            'fee_category_id' => $feeCategoryId,
            'description' => 'Test Bill',
            'amount' => $amount,
            'amount_paid' => $paid,
            'balance' => $amount - $paid,
            'status' => ($amount - $paid) <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
            'due_date' => '2026-06-01',
            'created_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
