<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\StreamNativeResultsEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Phase2DParityTest extends TestCase
{
    use RefreshDatabase;

    public function test_stream_native_averages_and_rankings_match_legacy_report_cards_for_non_withheld_students_and_exclude_withheld(): void
    {
        $this->withoutMiddleware();

        $setup = $this->foundation();
        $this->gradingScale();

        $subjectMath = $this->subject('Mathematics', 'MATH');
        $subjectEng = $this->subject('English', 'ENG');

        $typeTest = $this->assessmentType('Test', 50);
        $typeExam = $this->assessmentType('Exam', 50);

        $s1 = $this->student(['stream_id' => $setup['stream_id']]);
        $s2 = $this->student(['class_id' => $setup['class_id']]);
        $withheld = $this->student(['stream_id' => $setup['stream_id']]);

        $this->enrollSubjects($s1, [$subjectMath, $subjectEng], $setup);
        $this->enrollSubjects($s2, [$subjectMath, $subjectEng], $setup);
        $this->enrollSubjects($withheld, [$subjectMath, $subjectEng], $setup);

        $a1 = $this->assessment($setup, $subjectMath, $typeTest, 'Math Test', '2026-05-10');
        $a2 = $this->assessment($setup, $subjectMath, $typeExam, 'Math Exam', '2026-05-20');
        $a3 = $this->assessment($setup, $subjectEng, $typeTest, 'Eng Test', '2026-05-11');
        $a4 = $this->assessment($setup, $subjectEng, $typeExam, 'Eng Exam', '2026-05-21');

        $this->mark($a1, $s1, 80);
        $this->mark($a2, $s1, 70);
        $this->mark($a3, $s1, 60);
        $this->mark($a4, $s1, 65);

        $this->mark($a1, $s2, 75);
        $this->mark($a2, $s2, 68);
        $this->mark($a3, $s2, 55);
        $this->mark($a4, $s2, 62);

        $this->mark($a1, $withheld, 90);
        $this->mark($a2, $withheld, 85);
        $this->mark($a3, $withheld, 75);
        $this->mark($a4, $withheld, 80);

        $this->bill($withheld, $setup, 200, 0);

        StreamNativeResultsEngine::recomputeForStream($setup['academic_year_id'], $setup['term_id'], $setup['stream_id']);

        $resp1 = $this->postJson('/api/reports/student/generate', [
            'student_id' => $s1,
            'academic_year_id' => $setup['academic_year_id'],
            'term_id' => $setup['term_id'],
        ])->assertStatus(201);
        $legacy1 = $resp1->json('report');

        $resp2 = $this->postJson('/api/reports/student/generate', [
            'student_id' => $s2,
            'academic_year_id' => $setup['academic_year_id'],
            'term_id' => $setup['term_id'],
        ])->assertStatus(201);
        $legacy2 = $resp2->json('report');

        $agg1 = DB::table('results_aggregates')->where('student_id', $s1)->where('academic_year_id', $setup['academic_year_id'])->where('term_id', $setup['term_id'])->first();
        $agg2 = DB::table('results_aggregates')->where('student_id', $s2)->where('academic_year_id', $setup['academic_year_id'])->where('term_id', $setup['term_id'])->first();
        $aggW = DB::table('results_aggregates')->where('student_id', $withheld)->where('academic_year_id', $setup['academic_year_id'])->where('term_id', $setup['term_id'])->first();

        $this->assertNotNull($agg1?->term_average);
        $this->assertNotNull($agg2?->term_average);
        $this->assertTrue((bool) $aggW?->is_withheld);

        $this->assertNotNull($legacy1['overall_average']);
        $this->assertNotNull($legacy2['overall_average']);

        $this->assertEqualsWithDelta((float) $legacy1['overall_average'], (float) $agg1->term_average, 0.01);
        $this->assertEqualsWithDelta((float) $legacy2['overall_average'], (float) $agg2->term_average, 0.01);

        $legacyReportRows = DB::table('report_cards')
            ->where('academic_year_id', $setup['academic_year_id'])
            ->where('term_id', $setup['term_id'])
            ->where('stream_id', $setup['stream_id'])
            ->whereIn('student_id', [$s1, $s2])
            ->select('student_id', 'class_position')
            ->get()
            ->keyBy('student_id');

        $rankRows = DB::table('results_rankings')
            ->where('academic_year_id', $setup['academic_year_id'])
            ->where('term_id', $setup['term_id'])
            ->where('ranking_type', 'stream')
            ->where('ranking_id', $setup['stream_id'])
            ->whereNull('subject_id')
            ->select('student_id', 'rank')
            ->get()
            ->keyBy('student_id');

        $this->assertSame((int) $legacyReportRows[$s1]->class_position, (int) $rankRows[$s1]->rank);
        $this->assertSame((int) $legacyReportRows[$s2]->class_position, (int) $rankRows[$s2]->rank);

        $this->assertFalse($rankRows->has($withheld));
    }

    public function test_parent_stream_native_child_term_endpoint_hides_subjects_when_withheld(): void
    {
        $this->withoutMiddleware();

        $setup = $this->foundation();
        $this->gradingScale();

        $parent = User::factory()->create(['role' => 'parent']);

        $child = $this->student(['stream_id' => $setup['stream_id']]);
        DB::table('guardians')->insert([
            'user_id' => $parent->id,
            'student_id' => $child,
            'first_name' => 'Test',
            'last_name' => 'Parent',
            'phone' => '+263700000000',
            'relationship' => 'parent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subject = $this->subject('Mathematics', 'MATH');
        $this->enrollSubjects($child, [$subject], $setup);
        $type = $this->assessmentType('Test', 100);
        $a = $this->assessment($setup, $subject, $type, 'Quiz', '2026-05-10');
        $this->mark($a, $child, 80);

        $this->bill($child, $setup, 200, 0);

        StreamNativeResultsEngine::recomputeStudentTerm($child, $setup['academic_year_id'], $setup['term_id']);

        $this->actingAs($parent);

        $this->getJson('/api/parent/stream-native/child/' . $child . '/term?academic_year_id=' . $setup['academic_year_id'] . '&term_id=' . $setup['term_id'])
            ->assertOk()
            ->assertJsonPath('is_withheld', true)
            ->assertJsonCount(0, 'subjects');
    }

    public function test_stream_native_rankings_endpoint_supports_subject_rankings_with_all_subjects_and_subject_filter(): void
    {
        $this->withoutMiddleware();

        $setup = $this->foundation();
        $this->gradingScale();

        $subjectMath = $this->subject('Mathematics', 'MATH');
        $subjectEng = $this->subject('English', 'ENG');

        $typeExam = $this->assessmentType('Exam', 100);

        $s1 = $this->student(['stream_id' => $setup['stream_id']]);
        $s2 = $this->student(['stream_id' => $setup['stream_id']]);

        $this->enrollSubjects($s1, [$subjectMath, $subjectEng], $setup);
        $this->enrollSubjects($s2, [$subjectMath, $subjectEng], $setup);

        $aMath = $this->assessment($setup, $subjectMath, $typeExam, 'Math Exam', '2026-05-20');
        $aEng = $this->assessment($setup, $subjectEng, $typeExam, 'Eng Exam', '2026-05-21');

        $this->mark($aMath, $s1, 80);
        $this->mark($aEng, $s1, 70);
        $this->mark($aMath, $s2, 60);
        $this->mark($aEng, $s2, 65);

        StreamNativeResultsEngine::recomputeForStream($setup['academic_year_id'], $setup['term_id'], $setup['stream_id']);

        $all = $this->getJson('/api/stream-native/rankings?academic_year_id=' . $setup['academic_year_id'] . '&term_id=' . $setup['term_id'] . '&type=subject_stream&id=' . $setup['stream_id'])
            ->assertOk()
            ->json('data');

        $this->assertNotEmpty($all);
        $this->assertNotNull($all[0]['subject_id']);
        $this->assertArrayHasKey('subject_average', $all[0]);
        $this->assertArrayHasKey('subject_grade', $all[0]);

        $mathOnly = $this->getJson('/api/stream-native/rankings?academic_year_id=' . $setup['academic_year_id'] . '&term_id=' . $setup['term_id'] . '&type=subject_stream&id=' . $setup['stream_id'] . '&subject_id=' . $subjectMath)
            ->assertOk()
            ->json('data');

        $this->assertNotEmpty($mathOnly);
        foreach ($mathOnly as $row) {
            $this->assertSame($subjectMath, (int) $row['subject_id']);
        }
    }

    public function test_stream_native_student_year_includes_stream_rank_and_overall_grade(): void
    {
        $this->withoutMiddleware();

        $setup = $this->foundation();
        $this->gradingScale();

        $subjectMath = $this->subject('Mathematics', 'MATH');
        $typeExam = $this->assessmentType('Exam', 100);

        $s1 = $this->student(['stream_id' => $setup['stream_id']]);
        $this->enrollSubjects($s1, [$subjectMath], $setup);

        $aMath = $this->assessment($setup, $subjectMath, $typeExam, 'Math Exam', '2026-05-20');
        $this->mark($aMath, $s1, 80);

        StreamNativeResultsEngine::recomputeForStream($setup['academic_year_id'], $setup['term_id'], $setup['stream_id']);

        $resp = $this->getJson('/api/stream-native/results/student/' . $s1 . '/year?academic_year_id=' . $setup['academic_year_id'])
            ->assertOk()
            ->json();

        $this->assertNotEmpty($resp['terms']);
        $termRow = $resp['terms'][0];
        $this->assertArrayHasKey('stream_rank', $termRow);
        $this->assertArrayHasKey('stream_total', $termRow);
        $this->assertArrayHasKey('overall_grade', $termRow);
        $this->assertSame('A', $termRow['overall_grade']);
    }

    public function test_deprecated_stream_rankings_endpoint_returns_stream_native_values_and_sets_deprecation_header(): void
    {
        $this->withoutMiddleware();

        $setup = $this->foundation();
        $this->gradingScale();

        $subjectMath = $this->subject('Mathematics', 'MATH');
        $typeExam = $this->assessmentType('Exam', 100);

        $s1 = $this->student(['stream_id' => $setup['stream_id']]);
        $s2 = $this->student(['stream_id' => $setup['stream_id']]);
        $this->enrollSubjects($s1, [$subjectMath], $setup);
        $this->enrollSubjects($s2, [$subjectMath], $setup);

        $aMath = $this->assessment($setup, $subjectMath, $typeExam, 'Math Exam', '2026-05-20');
        $this->mark($aMath, $s1, 80);
        $this->mark($aMath, $s2, 60);

        StreamNativeResultsEngine::recomputeForStream($setup['academic_year_id'], $setup['term_id'], $setup['stream_id']);

        $sn = $this->getJson('/api/stream-native/rankings?academic_year_id=' . $setup['academic_year_id'] . '&term_id=' . $setup['term_id'] . '&type=stream&id=' . $setup['stream_id'])
            ->assertOk()
            ->json('data');

        $legacy = $this->getJson('/api/reports/rankings/stream?academic_year_id=' . $setup['academic_year_id'] . '&term_id=' . $setup['term_id'] . '&stream_id=' . $setup['stream_id'])
            ->assertOk()
            ->assertHeader('Deprecation', 'true')
            ->json();

        $snByStudent = collect($sn)->keyBy('student_id');
        foreach ($legacy as $row) {
            $sid = (int) $row['student_id'];
            $this->assertTrue($snByStudent->has($sid));
            $this->assertSame((int) $row['class_position'], (int) $snByStudent[$sid]['rank']);
            $this->assertEqualsWithDelta((float) $row['overall_average'], (float) $snByStudent[$sid]['term_average'], 0.01);
        }
    }

    public function test_deprecated_subject_rankings_endpoint_returns_stream_native_values_and_sets_deprecation_header(): void
    {
        $this->withoutMiddleware();

        $setup = $this->foundation();
        $this->gradingScale();

        $subjectMath = $this->subject('Mathematics', 'MATH');
        $subjectEng = $this->subject('English', 'ENG');
        $typeExam = $this->assessmentType('Exam', 100);

        $s1 = $this->student(['stream_id' => $setup['stream_id']]);
        $s2 = $this->student(['stream_id' => $setup['stream_id']]);
        $this->enrollSubjects($s1, [$subjectMath, $subjectEng], $setup);
        $this->enrollSubjects($s2, [$subjectMath, $subjectEng], $setup);

        $aMath = $this->assessment($setup, $subjectMath, $typeExam, 'Math Exam', '2026-05-20');
        $aEng = $this->assessment($setup, $subjectEng, $typeExam, 'Eng Exam', '2026-05-21');
        $this->mark($aMath, $s1, 80);
        $this->mark($aEng, $s1, 70);
        $this->mark($aMath, $s2, 60);
        $this->mark($aEng, $s2, 65);

        StreamNativeResultsEngine::recomputeForStream($setup['academic_year_id'], $setup['term_id'], $setup['stream_id']);

        $sn = $this->getJson('/api/stream-native/rankings?academic_year_id=' . $setup['academic_year_id'] . '&term_id=' . $setup['term_id'] . '&type=subject_stream&id=' . $setup['stream_id'] . '&subject_id=' . $subjectMath)
            ->assertOk()
            ->json('data');

        $legacy = $this->getJson('/api/reports/rankings/subject?academic_year_id=' . $setup['academic_year_id'] . '&term_id=' . $setup['term_id'] . '&stream_id=' . $setup['stream_id'] . '&subject_id=' . $subjectMath)
            ->assertOk()
            ->assertHeader('Deprecation', 'true')
            ->json();

        $snByStudent = collect($sn)->keyBy('student_id');
        foreach ($legacy as $row) {
            $sid = (int) $row['student_id'];
            $this->assertTrue($snByStudent->has($sid));
            $this->assertSame((int) $row['subject_position'], (int) $snByStudent[$sid]['rank']);
            $this->assertEqualsWithDelta((float) $row['subject_average'], (float) $snByStudent[$sid]['subject_average'], 0.01);
        }
    }

    private function foundation(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);

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
            'user_id' => $admin->id,
            'category_id' => $categoryId,
            'form_id' => $formId,
            'stream_id' => $streamId,
            'academic_year_id' => $academicYearId,
            'term_id' => $termId,
            'class_id' => $classId,
        ];
    }

    private function gradingScale(): void
    {
        DB::table('grading_scales')->insert([
            ['name' => 'Default', 'min_percentage' => 0, 'max_percentage' => 39.99, 'grade' => 'U', 'remark' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Default', 'min_percentage' => 40, 'max_percentage' => 49.99, 'grade' => 'D', 'remark' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Default', 'min_percentage' => 50, 'max_percentage' => 59.99, 'grade' => 'C', 'remark' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Default', 'min_percentage' => 60, 'max_percentage' => 69.99, 'grade' => 'B', 'remark' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Default', 'min_percentage' => 70, 'max_percentage' => 100, 'grade' => 'A', 'remark' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
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
            'admission_number' => 'DGI-P2D-' . $count,
            'student_number' => 'STU-P2D-' . $count,
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
                    'created_by' => $setup['user_id'],
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
                'created_by' => $setup['user_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function assessment(array $setup, int $subjectId, int $typeId, string $title, string $date): int
    {
        return DB::table('assessments')->insertGetId([
            'assessment_number' => 'A-P2D-' . uniqid(),
            'academic_year_id' => $setup['academic_year_id'],
            'term_id' => $setup['term_id'],
            'stream_id' => $setup['stream_id'],
            'subject_id' => $subjectId,
            'assessment_type_id' => $typeId,
            'title' => $title,
            'total_marks' => 100,
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
            'bill_number' => 'BILL-P2D-' . uniqid(),
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
            'created_by' => $setup['user_id'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
