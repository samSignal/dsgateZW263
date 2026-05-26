<?php

namespace Tests\Feature;

use App\Support\StreamNativeResultsEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Phase2CStreamNativeApisTest extends TestCase
{
    use RefreshDatabase;

    public function test_results_and_rankings_and_transcript_and_progression_endpoints_are_consistent_and_withheld_excluded(): void
    {
        $this->withoutMiddleware();

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

        StreamNativeResultsEngine::recomputeForStream($setup['academic_year_id'], $setup['term_id'], $setup['stream_id']);

        $this->getJson('/api/stream-native/results/student/' . $legacyStudent . '/term?academic_year_id=' . $setup['academic_year_id'] . '&term_id=' . $setup['term_id'])
            ->assertOk()
            ->assertJsonPath('student.stream_id', $setup['stream_id'])
            ->assertJsonPath('student.form_id', $setup['form_id'])
            ->assertJsonPath('student.category_id', $setup['category_id'])
            ->assertJsonPath('student.class_id', $setup['class_id'])
            ->assertJsonPath('aggregate.academic_year_id', $setup['academic_year_id'])
            ->assertJsonPath('aggregate.term_id', $setup['term_id']);

        $this->getJson('/api/stream-native/transcript/student/' . $legacyStudent)
            ->assertOk()
            ->assertJsonPath('student.stream_id', $setup['stream_id'])
            ->assertJsonPath('student.class_id', $setup['class_id']);

        $rankResp = $this->getJson('/api/stream-native/rankings?academic_year_id=' . $setup['academic_year_id'] . '&term_id=' . $setup['term_id'] . '&type=stream&id=' . $setup['stream_id'])
            ->assertOk();

        $ids = collect($rankResp->json('data'))->pluck('student_id')->all();
        $this->assertContains($directStudent, $ids);
        $this->assertContains($legacyStudent, $ids);
        $this->assertNotContains($withheldStudent, $ids);

        $this->getJson('/api/stream-native/progression/student/' . $directStudent . '/status?academic_year_id=' . $setup['academic_year_id'] . '&term_id=' . $setup['term_id'])
            ->assertOk()
            ->assertJsonPath('decision.decision', 'promote');

        $this->getJson('/api/stream-native/report-card/student/' . $directStudent . '/term?academic_year_id=' . $setup['academic_year_id'] . '&term_id=' . $setup['term_id'])
            ->assertOk()
            ->assertJsonPath('student.stream_id', $setup['stream_id']);
    }

    private function foundation(): array
    {
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
        $termId = DB::table('terms')->insertGetId([
            'academic_year_id' => $academicYearId,
            'name' => 'Term 1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-04-01',
            'is_current' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $classId = DB::table('classes')->insertGetId([
            'class_name' => 'Form 5',
            'stream' => 'Sciences',
            'academic_year' => '2026',
            'capacity' => 40,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'category_id' => $categoryId,
            'form_id' => $formId,
            'stream_id' => $streamId,
            'academic_year_id' => $academicYearId,
            'term_id' => $termId,
            'class_id' => $classId,
        ];
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
            'admission_number' => 'DGI-P2C-' . $count,
            'student_number' => 'STU-P2C-' . $count,
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
        $userId = (int) DB::table('users')->insertGetId([
            'name' => 'Admin',
            'email' => uniqid('admin_') . '@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'role' => 'admin',
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('assessments')->insertGetId([
            'assessment_number' => 'A-P2C-' . uniqid(),
            'academic_year_id' => $setup['academic_year_id'],
            'term_id' => $setup['term_id'],
            'stream_id' => $setup['stream_id'],
            'subject_id' => $subjectId,
            'assessment_type_id' => $typeId,
            'title' => $title,
            'total_marks' => $totalMarks,
            'assessment_date' => $date,
            'created_by' => $userId,
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
            'bill_number' => 'BILL-P2C-' . uniqid(),
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

