<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\StudentStreamResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HighRiskAcademicEngineStabilizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_generation_and_publishing_work_for_direct_and_legacy_students_and_rankings_include_both(): void
    {
        $this->withoutMiddleware();
        $user = User::factory()->create();
        $this->actingAs($user);

        $setup = $this->foundation();
        $subjectId = $this->subject('Mathematics', 'MATH');
        $streamSubjectId = $this->streamSubject($setup['academic_year_id'], $setup['term_id'], $setup['form_id'], $setup['stream_id'], $subjectId);

        $directStudentId = $this->student(['stream_id' => $setup['stream_id']]);
        $legacyStudentId = $this->student(['class_id' => $setup['class_id']]);

        $this->studentSubject($directStudentId, $streamSubjectId, $subjectId, $setup['academic_year_id'], $setup['term_id']);
        $this->studentSubject($legacyStudentId, $streamSubjectId, $subjectId, $setup['academic_year_id'], $setup['term_id']);

        $this->postJson('/api/reports/student/generate', [
            'student_id' => $directStudentId,
            'academic_year_id' => $setup['academic_year_id'],
            'term_id' => $setup['term_id'],
        ])
            ->assertStatus(201)
            ->assertJsonPath('report.student_id', $directStudentId)
            ->assertJsonPath('report.stream_id', $setup['stream_id'])
            ->assertJsonPath('report.form_id', $setup['form_id']);

        $this->postJson('/api/reports/student/generate', [
            'student_id' => $legacyStudentId,
            'academic_year_id' => $setup['academic_year_id'],
            'term_id' => $setup['term_id'],
        ])
            ->assertStatus(201)
            ->assertJsonPath('report.student_id', $legacyStudentId)
            ->assertJsonPath('report.stream_id', $setup['stream_id'])
            ->assertJsonPath('report.form_id', $setup['form_id']);

        $reports = DB::table('report_cards')->where('academic_year_id', $setup['academic_year_id'])->where('term_id', $setup['term_id'])->where('stream_id', $setup['stream_id'])->get();
        $this->assertCount(2, $reports);
        $this->assertTrue($reports->every(fn ($r) => (int) $r->stream_total_students === 2));

        $reportId = (int) DB::table('report_cards')->where('student_id', $directStudentId)->where('academic_year_id', $setup['academic_year_id'])->where('term_id', $setup['term_id'])->value('id');
        $this->postJson('/api/reports/' . $reportId . '/approve')->assertOk();
        $this->postJson('/api/reports/' . $reportId . '/publish')->assertOk();

        $this->assertSame('published', DB::table('report_cards')->where('id', $reportId)->value('status'));
    }

    public function test_attendance_session_generation_includes_direct_and_legacy_students_in_stream(): void
    {
        $this->withoutMiddleware();
        $user = User::factory()->create();
        $this->actingAs($user);

        $setup = $this->foundation();
        $this->student(['stream_id' => $setup['stream_id']]);
        $this->student(['class_id' => $setup['class_id']]);

        $resp = $this->postJson('/api/discipline/attendance/sessions', [
            'academic_year_id' => $setup['academic_year_id'],
            'term_id' => $setup['term_id'],
            'form_id' => $setup['form_id'],
            'stream_id' => $setup['stream_id'],
            'attendance_date' => '2026-05-25',
            'session_type' => 'morning',
            'subject_id' => null,
        ])->assertStatus(201);

        $sessionId = (int) $resp->json('session_id');
        $this->assertSame(2, (int) DB::table('student_attendance_records')->where('attendance_session_id', $sessionId)->count());
    }

    public function test_student_billing_index_and_payments_index_include_stream_first_fields(): void
    {
        $this->withoutMiddleware();
        $user = User::factory()->create();
        $this->actingAs($user);

        $setup = $this->foundation();
        $studentId = $this->student(['class_id' => $setup['class_id']]);
        $feeCategoryId = $this->feeCategory('Tuition');

        DB::table('student_bills')->insert([
            'bill_number' => 'BILL-TEST-00001',
            'student_id' => $studentId,
            'academic_year_id' => $setup['academic_year_id'],
            'term_id' => $setup['term_id'],
            'fee_structure_id' => null,
            'fee_category_id' => $feeCategoryId,
            'description' => 'Test Bill',
            'amount' => 100,
            'amount_paid' => 0,
            'balance' => 100,
            'status' => 'unpaid',
            'due_date' => '2026-06-01',
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('finance_payments')->insert([
            'receipt_number' => 'RCPT-TEST-00001',
            'student_id' => $studentId,
            'academic_year_id' => $setup['academic_year_id'],
            'term_id' => $setup['term_id'],
            'amount' => 50,
            'payment_method' => 'cash',
            'reference_number' => null,
            'payer_name' => null,
            'payer_phone' => null,
            'received_by' => $user->id,
            'payment_date' => '2026-05-25',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/finance/bills')
            ->assertOk()
            ->assertJsonPath('data.0.stream_id', $setup['stream_id'])
            ->assertJsonPath('data.0.form_id', $setup['form_id'])
            ->assertJsonPath('data.0.category_id', $setup['category_id'])
            ->assertJsonPath('data.0.class_id', $setup['class_id']);

        $this->getJson('/api/finance/payments')
            ->assertOk()
            ->assertJsonPath('data.0.stream_id', $setup['stream_id'])
            ->assertJsonPath('data.0.form_id', $setup['form_id'])
            ->assertJsonPath('data.0.category_id', $setup['category_id'])
            ->assertJsonPath('data.0.class_id', $setup['class_id']);
    }

    public function test_student_ids_for_stream_have_no_duplicates_and_include_stream_and_legacy_students(): void
    {
        $setup = $this->foundation();

        $direct = $this->student(['stream_id' => $setup['stream_id']]);
        $legacy = $this->student(['class_id' => $setup['class_id']]);
        $both = $this->student(['stream_id' => $setup['stream_id'], 'class_id' => $setup['class_id']]);

        $ids = StudentStreamResolver::studentIdsForStream($setup['stream_id'], $setup['academic_year_id']);
        sort($ids);

        $this->assertSame([$direct, $legacy, $both], $ids);
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

    private function streamSubject(int $academicYearId, int $termId, int $formId, int $streamId, int $subjectId): int
    {
        return DB::table('stream_subjects')->insertGetId([
            'academic_year_id' => $academicYearId,
            'term_id' => $termId,
            'form_id' => $formId,
            'stream_id' => $streamId,
            'subject_id' => $subjectId,
            'is_compulsory' => true,
            'created_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function studentSubject(int $studentId, int $streamSubjectId, int $subjectId, int $academicYearId, int $termId): int
    {
        return DB::table('student_subjects')->insertGetId([
            'student_id' => $studentId,
            'stream_subject_id' => $streamSubjectId,
            'subject_id' => $subjectId,
            'academic_year_id' => $academicYearId,
            'term_id' => $termId,
            'is_compulsory' => true,
            'enrollment_status' => 'active',
            'created_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function feeCategory(string $name): int
    {
        return DB::table('fee_categories')->insertGetId([
            'name' => $name,
            'description' => $name,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
