<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssessmentEngineStabilizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_assessment_creation_includes_direct_and_legacy_students_without_duplicates_and_exposes_stream_first_fields(): void
    {
        $this->withoutMiddleware();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $setup = $this->foundation();
        $assessmentTypeId = $this->assessmentType('Test', 100);
        $subjectId = $this->subject('Mathematics', 'MATH');
        $streamSubjectId = $this->streamSubject($setup, $subjectId);

        $directStudentId = $this->student(['stream_id' => $setup['stream_id']]);
        $legacyStudentId = $this->student(['class_id' => $setup['class_id']]);
        $bothStudentId = $this->student(['stream_id' => $setup['stream_id'], 'class_id' => $setup['class_id']]);

        $otherStreamId = $this->stream($setup['form_id'], 'Arts', $setup['category_id']);
        $otherStudentId = $this->student(['stream_id' => $otherStreamId]);

        $this->studentSubject($directStudentId, $streamSubjectId, $subjectId, $setup);
        $this->studentSubject($legacyStudentId, $streamSubjectId, $subjectId, $setup);
        $this->studentSubject($bothStudentId, $streamSubjectId, $subjectId, $setup);
        $this->studentSubject($otherStudentId, $streamSubjectId, $subjectId, $setup);

        $resp = $this->postJson('/api/assessments', [
            'academic_year_id' => $setup['academic_year_id'],
            'term_id' => $setup['term_id'],
            'stream_id' => $setup['stream_id'],
            'subject_id' => $subjectId,
            'assessment_type_id' => $assessmentTypeId,
            'title' => 'Mid Term Test',
            'total_marks' => 100,
            'assessment_date' => '2026-05-20',
            'status' => 'open',
        ])
            ->assertStatus(201)
            ->assertJsonPath('assessment.stream_id', $setup['stream_id'])
            ->assertJsonPath('assessment.form_id', $setup['form_id'])
            ->assertJsonPath('assessment.category_id', $setup['category_id'])
            ->assertJsonPath('assessment.category_name', 'Sciences');

        $assessmentId = (int) $resp->json('assessment.id');

        $marks = DB::table('assessment_marks')->where('assessment_id', $assessmentId)->get();
        $this->assertSame(3, $marks->count());
        $this->assertSame(3, $marks->pluck('student_id')->unique()->count());
        $this->assertFalse($marks->pluck('student_id')->contains($otherStudentId));
    }

    public function test_marks_entry_submit_and_approval_are_consistent_and_report_generation_remains_compatible(): void
    {
        $this->withoutMiddleware();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $setup = $this->foundation();
        $this->gradingScale();
        $assessmentTypeId = $this->assessmentType('Test', 100);
        $subjectId = $this->subject('Mathematics', 'MATH');
        $streamSubjectId = $this->streamSubject($setup, $subjectId);

        $directStudentId = $this->student(['stream_id' => $setup['stream_id']]);
        $legacyStudentId = $this->student(['class_id' => $setup['class_id']]);

        $this->studentSubject($directStudentId, $streamSubjectId, $subjectId, $setup);
        $this->studentSubject($legacyStudentId, $streamSubjectId, $subjectId, $setup);

        $resp = $this->postJson('/api/assessments', [
            'academic_year_id' => $setup['academic_year_id'],
            'term_id' => $setup['term_id'],
            'stream_id' => $setup['stream_id'],
            'subject_id' => $subjectId,
            'assessment_type_id' => $assessmentTypeId,
            'title' => 'Weekly Quiz',
            'total_marks' => 20,
            'assessment_date' => '2026-05-21',
            'status' => 'open',
        ])->assertStatus(201);

        $assessmentId = (int) $resp->json('assessment.id');

        $this->postJson('/api/assessments/' . $assessmentId . '/marks', [
            'marks' => [
                ['student_id' => $directStudentId, 'status' => 'entered', 'mark_obtained' => 18, 'teacher_comment' => null],
                ['student_id' => $legacyStudentId, 'status' => 'entered', 'mark_obtained' => 10, 'teacher_comment' => null],
            ],
        ])->assertOk();

        $this->postJson('/api/assessments/' . $assessmentId . '/marks/submit')->assertOk();
        $this->postJson('/api/assessments/' . $assessmentId . '/approve')->assertOk();

        $this->assertSame('approved', DB::table('assessments')->where('id', $assessmentId)->value('status'));
        $this->assertSame(2, (int) DB::table('assessment_marks')->where('assessment_id', $assessmentId)->where('status', 'entered')->count());

        $this->getJson('/api/assessments/' . $assessmentId)
            ->assertOk()
            ->assertJsonPath('stream_id', $setup['stream_id'])
            ->assertJsonPath('form_id', $setup['form_id'])
            ->assertJsonPath('category_id', $setup['category_id'])
            ->assertJsonPath('marks.0.stream_id', $setup['stream_id'])
            ->assertJsonPath('marks.0.form_id', $setup['form_id'])
            ->assertJsonPath('marks.0.category_id', $setup['category_id'])
            ->assertJsonFragment(['student_id' => $legacyStudentId, 'class_id' => $setup['class_id']]);

        $this->postJson('/api/reports/student/generate', [
            'student_id' => $directStudentId,
            'academic_year_id' => $setup['academic_year_id'],
            'term_id' => $setup['term_id'],
        ])
            ->assertStatus(201)
            ->assertJsonPath('report.stream_id', $setup['stream_id'])
            ->assertJsonPath('report.form_id', $setup['form_id']);

        $this->postJson('/api/reports/student/generate', [
            'student_id' => $legacyStudentId,
            'academic_year_id' => $setup['academic_year_id'],
            'term_id' => $setup['term_id'],
        ])
            ->assertStatus(201)
            ->assertJsonPath('report.stream_id', $setup['stream_id'])
            ->assertJsonPath('report.form_id', $setup['form_id']);
    }

    private function foundation(): array
    {
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
        $streamId = $this->stream($formId, 'Sciences', $categoryId);
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

        return compact('categoryId', 'formId', 'streamId', 'academicYearId', 'termId', 'classId') + [
            'category_id' => $categoryId,
            'form_id' => $formId,
            'stream_id' => $streamId,
            'academic_year_id' => $academicYearId,
            'term_id' => $termId,
            'class_id' => $classId,
        ];
    }

    private function stream(int $formId, string $name, int $categoryId): int
    {
        return DB::table('streams')->insertGetId([
            'form_id' => $formId,
            'category_id' => $categoryId,
            'name' => $name,
            'capacity' => 40,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function student(array $overrides = []): int
    {
        static $count = 0;
        $count++;
        return DB::table('students')->insertGetId([
            'admission_number' => 'DGI-TEST-' . $count,
            'student_number' => 'STU-TEST-' . $count,
            'first_name' => 'Test',
            'last_name' => 'Student ' . $count,
            'admission_date' => '2026-01-15',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
            ...$overrides,
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

    private function gradingScale(): void
    {
        DB::table('grading_scales')->insert([
            ['name' => 'Default', 'min_percentage' => 0, 'max_percentage' => 49.99, 'grade' => 'U', 'remark' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Default', 'min_percentage' => 50, 'max_percentage' => 59.99, 'grade' => 'C', 'remark' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Default', 'min_percentage' => 60, 'max_percentage' => 69.99, 'grade' => 'B', 'remark' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Default', 'min_percentage' => 70, 'max_percentage' => 100, 'grade' => 'A', 'remark' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
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

    private function streamSubject(array $setup, int $subjectId): int
    {
        return DB::table('stream_subjects')->insertGetId([
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

    private function studentSubject(int $studentId, int $streamSubjectId, int $subjectId, array $setup): void
    {
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
