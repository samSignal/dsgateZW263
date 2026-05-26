<?php

namespace Tests\Feature;

use App\Support\StudentStreamResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentStreamCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_with_stream_id_resolves_stream_form_and_category(): void
    {
        $setup = $this->foundation();
        $studentId = $this->student(['stream_id' => $setup['stream_id']]);

        $resolved = StudentStreamResolver::resolveByStudentId($studentId);

        $this->assertSame($setup['stream_id'], $resolved->stream_id);
        $this->assertSame($setup['form_id'], $resolved->form_id);
        $this->assertSame($setup['category_id'], $resolved->category_id);
        $this->assertSame('stream_id', $resolved->source);
        $this->assertSame('Form 5 Sciences', $resolved->display_label);
    }

    public function test_student_with_only_class_id_falls_back_to_legacy_mapping(): void
    {
        $setup = $this->foundation();
        $studentId = $this->student(['class_id' => $setup['class_id']]);

        $resolved = StudentStreamResolver::resolveByStudentId($studentId);

        $this->assertSame($setup['stream_id'], $resolved->stream_id);
        $this->assertSame($setup['form_id'], $resolved->form_id);
        $this->assertSame('class_id', $resolved->source);
        $this->assertTrue($resolved->is_mapped);
    }

    public function test_category_resolution_works_from_legacy_class_fallback(): void
    {
        $setup = $this->foundation();
        $studentId = $this->student(['class_id' => $setup['class_id']]);

        $resolved = StudentStreamResolver::resolveByStudentId($studentId);

        $this->assertSame('Sciences', $resolved->category_name);
        $this->assertSame('SCI', $resolved->category_code);
    }

    public function test_unmapped_legacy_class_is_reported_without_failure(): void
    {
        $classId = DB::table('classes')->insertGetId([
            'class_name' => 'Form 9',
            'stream' => 'Z',
            'academic_year' => '2026',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $studentId = $this->student(['class_id' => $classId]);

        $resolved = StudentStreamResolver::resolveByStudentId($studentId);
        $report = StudentStreamResolver::backfillStudents(true);

        $this->assertFalse($resolved->is_mapped);
        $this->assertSame('unmapped', $resolved->source);
        $this->assertCount(1, $report['unmapped']);
        $this->assertSame($studentId, $report['unmapped'][0]['student_id']);
    }

    public function test_backfill_populates_stream_form_category_and_academic_year_without_overwriting_existing_stream(): void
    {
        $setup = $this->foundation();
        $existingStreamId = $this->stream($setup['form_id'], 'Arts', null);
        $legacyStudentId = $this->student(['class_id' => $setup['class_id']]);
        $directStudentId = $this->student(['class_id' => $setup['class_id'], 'stream_id' => $existingStreamId]);

        $report = StudentStreamResolver::backfillStudents();

        $legacy = DB::table('students')->where('id', $legacyStudentId)->first();
        $direct = DB::table('students')->where('id', $directStudentId)->first();

        $this->assertSame(1, $report['updated']);
        $this->assertSame(1, $report['skipped_existing_stream']);
        $this->assertSame($setup['stream_id'], (int) $legacy->stream_id);
        $this->assertSame($setup['form_id'], (int) $legacy->form_id);
        $this->assertSame($setup['category_id'], (int) $legacy->category_id);
        $this->assertSame($setup['academic_year_id'], (int) $legacy->academic_year_id);
        $this->assertSame($existingStreamId, (int) $direct->stream_id);
    }

    public function test_backfill_command_reports_success(): void
    {
        $setup = $this->foundation();
        $this->student(['class_id' => $setup['class_id']]);

        $exitCode = Artisan::call('dgi:backfill-student-streams');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Backfill complete.', Artisan::output());
        $this->assertDatabaseHas('students', ['stream_id' => $setup['stream_id']]);
    }

    public function test_student_index_read_path_uses_stream_label_for_direct_stream_students(): void
    {
        $this->withoutMiddleware();
        $setup = $this->foundation();
        $this->student(['stream_id' => $setup['stream_id']]);

        $this->getJson('/api/students')
            ->assertOk()
            ->assertJsonPath('data.0.class_display', 'Form 5 Sciences')
            ->assertJsonPath('data.0.resolved_category_name', 'Sciences')
            ->assertJsonPath('data.0.stream_id', $setup['stream_id'])
            ->assertJsonPath('data.0.form_id', $setup['form_id'])
            ->assertJsonPath('data.0.category_id', $setup['category_id']);
    }

    public function test_legacy_classes_endpoint_still_functions(): void
    {
        $this->withoutMiddleware();
        $setup = $this->foundation();

        $this->getJson('/api/classes')
            ->assertOk()
            ->assertJsonFragment(['id' => $setup['class_id'], 'class_name' => 'Form 5', 'stream' => 'Sciences']);
    }

    public function test_parent_child_finance_children_endpoint_returns_stream_first_fields(): void
    {
        $this->withoutMiddleware();
        $setup = $this->foundation();
        $studentId = $this->student(['class_id' => $setup['class_id']]);

        DB::table('guardians')->insert([
            'user_id' => null,
            'student_id' => $studentId,
            'first_name' => 'Parent',
            'last_name' => 'One',
            'email' => 'parent@example.com',
            'phone' => '0770000000',
            'relationship' => 'parent',
            'address' => null,
            'city' => null,
            'country' => null,
            'occupation' => null,
            'is_primary_contact' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/parent/finance/children')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $studentId,
                'stream_id' => $setup['stream_id'],
                'form_id' => $setup['form_id'],
                'category_id' => $setup['category_id'],
                'class_id' => $setup['class_id'],
            ]);
    }

    public function test_finance_balances_by_form_groups_using_resolved_form_for_legacy_and_direct_students(): void
    {
        $this->withoutMiddleware();
        $setup = $this->foundation();
        $termId = $this->term($setup['academic_year_id'], 'Term 1');
        $feeCategoryId = $this->feeCategory('Tuition');

        $directStudentId = $this->student(['stream_id' => $setup['stream_id']]);
        $legacyStudentId = $this->student(['class_id' => $setup['class_id']]);

        $this->studentBill($directStudentId, $setup['academic_year_id'], $termId, $feeCategoryId, 1000.00, 250.00);
        $this->studentBill($legacyStudentId, $setup['academic_year_id'], $termId, $feeCategoryId, 500.00, 0.00);

        $this->getJson('/api/finance/reports/balances-by-form?academic_year_id=' . $setup['academic_year_id'] . '&term_id=' . $termId)
            ->assertOk()
            ->assertJsonFragment([
                'form_id' => $setup['form_id'],
                'form_name' => 'Form 5',
                'student_count' => 2,
            ]);
    }

    public function test_finance_debtors_endpoint_includes_stream_first_fields_and_supports_form_filter(): void
    {
        $this->withoutMiddleware();
        $setup = $this->foundation();
        $termId = $this->term($setup['academic_year_id'], 'Term 1');
        $feeCategoryId = $this->feeCategory('Tuition');

        $studentId = $this->student(['class_id' => $setup['class_id']]);
        $this->studentBill($studentId, $setup['academic_year_id'], $termId, $feeCategoryId, 300.00, 0.00);

        $this->getJson('/api/finance/reports/debtors?academic_year_id=' . $setup['academic_year_id'] . '&term_id=' . $termId . '&form_id=' . $setup['form_id'])
            ->assertOk()
            ->assertJsonFragment([
                'student_id' => $studentId,
                'stream_id' => $setup['stream_id'],
                'form_id' => $setup['form_id'],
                'category_id' => $setup['category_id'],
                'class_id' => $setup['class_id'],
            ]);
    }

    public function test_student_timetable_read_path_uses_resolved_stream_id_from_legacy_class(): void
    {
        $this->withoutMiddleware();
        $setup = $this->foundation();
        $termId = $this->term($setup['academic_year_id'], 'Term 1');

        $studentId = $this->student(['class_id' => $setup['class_id']]);
        $subjectId = $this->subject('Mathematics', 'MATH');
        $teacherId = $this->staffMember('T001', 'Jane', 'Teacher');
        $periodId = DB::table('timetable_periods')->value('id') ?? DB::table('timetable_periods')->insertGetId([
            'name' => 'Period 1',
            'period_number' => 1,
            'start_time' => '08:00:00',
            'end_time' => '08:40:00',
            'is_break' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('school_timetables')->insert([
            'academic_year_id' => $setup['academic_year_id'],
            'term_id' => $termId,
            'form_id' => $setup['form_id'],
            'stream_id' => $setup['stream_id'],
            'subject_id' => $subjectId,
            'teacher_id' => $teacherId,
            'room_id' => null,
            'period_id' => $periodId,
            'day_of_week' => 'monday',
            'start_time' => '08:00:00',
            'end_time' => '08:40:00',
            'timetable_type' => 'class',
            'remarks' => null,
            'created_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/student/timetable?academic_year_id=' . $setup['academic_year_id'] . '&term_id=' . $termId)
            ->assertOk()
            ->assertJsonPath('student.id', $studentId)
            ->assertJsonPath('student.stream_id', $setup['stream_id'])
            ->assertJsonPath('entries.0.subject_name', 'Mathematics');
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
        $classId = DB::table('classes')->insertGetId([
            'class_name' => 'Form 5',
            'stream' => 'Sciences',
            'academic_year' => '2026',
            'capacity' => 40,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return compact('categoryId', 'formId', 'streamId', 'academicYearId', 'classId') + [
            'category_id' => $categoryId,
            'form_id' => $formId,
            'stream_id' => $streamId,
            'academic_year_id' => $academicYearId,
            'class_id' => $classId,
        ];
    }

    private function stream(int $formId, string $name, ?int $categoryId): int
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

    private function term(int $academicYearId, string $name): int
    {
        return DB::table('terms')->insertGetId([
            'academic_year_id' => $academicYearId,
            'name' => $name,
            'start_date' => '2026-01-01',
            'end_date' => '2026-04-01',
            'is_current' => false,
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

    private function studentBill(int $studentId, int $academicYearId, int $termId, int $feeCategoryId, float $amount, float $paid): int
    {
        static $billCount = 0;
        $billCount++;

        return DB::table('student_bills')->insertGetId([
            'bill_number' => 'BILL-TEST-' . $billCount,
            'student_id' => $studentId,
            'academic_year_id' => $academicYearId,
            'term_id' => $termId,
            'fee_structure_id' => null,
            'fee_category_id' => $feeCategoryId,
            'description' => 'Test bill',
            'amount' => $amount,
            'amount_paid' => $paid,
            'balance' => $amount - $paid,
            'status' => ($amount - $paid) <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
            'due_date' => '2026-03-01',
            'created_by' => null,
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function staffMember(string $staffNumber, string $firstName, string $lastName): int
    {
        $departmentId = DB::table('departments')->insertGetId([
            'name' => 'Academics',
            'description' => 'Academics Department',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('staff_members')->insertGetId([
            'user_id' => null,
            'staff_number' => $staffNumber,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'gender' => 'female',
            'date_of_birth' => '1990-01-01',
            'phone' => '0771111111',
            'email' => strtolower($staffNumber) . '@example.com',
            'address' => null,
            'national_id' => null,
            'department_id' => $departmentId,
            'job_title' => 'Teacher',
            'employment_type' => 'full_time',
            'employment_date' => '2020-01-01',
            'profile_photo' => null,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
