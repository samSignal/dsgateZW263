<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdmissionsPhase1AV2Test extends TestCase
{
    use RefreshDatabase;

    private function seedAcademic(): array
    {
        $yearId = DB::table('academic_years')->insertGetId([
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $formId = DB::table('forms')->insertGetId([
            'name' => 'Form 1',
            'level' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $catId = DB::table('categories')->insertGetId([
            'name' => 'Sciences',
            'code' => 'SCI',
            'description' => 'Science pathway',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$yearId, $formId, $catId];
    }

    public function test_v2_draft_start_issues_plaintext_token_once_but_does_not_store_plaintext_in_admission_applications(): void
    {
        [$yearId, $formId, $catId] = $this->seedAcademic();

        $resp = $this->postJson('/api/admissions/v2/draft/start', [
            'application_type' => 'new_intake',
            'academic_year_id' => $yearId,
            'applying_form_id' => $formId,
            'preferred_category_id' => $catId,
            'student_first_name' => 'John',
            'student_last_name' => 'Doe',
            'gender' => 'male',
            'date_of_birth' => '2012-02-03',
            'birth_certificate_number' => 'BC-1234',
            'guardian_name' => 'Jane Doe',
            'guardian_phone' => '+263700000000',
            'guardian_email' => 'parent@example.com',
            'emergency_phone' => '+263711111111',
        ])->assertOk();

        $issued = $resp->json('data.issued_token');
        $appId = (int) $resp->json('data.application_id');

        $this->assertNotEmpty($issued);
        $this->assertStringStartsWith('DGI-', $issued);

        $stored = DB::table('admission_applications')->where('id', $appId)->value('tracking_token');
        $this->assertNotSame($issued, $stored);
        $this->assertStringStartsWith('V2-', (string) $stored);
    }

    public function test_v2_draft_save_enforces_revision_concurrency(): void
    {
        [$yearId, $formId, $catId] = $this->seedAcademic();

        $start = $this->postJson('/api/admissions/v2/draft/start', [
            'application_type' => 'new_intake',
            'academic_year_id' => $yearId,
            'applying_form_id' => $formId,
            'preferred_category_id' => $catId,
            'student_first_name' => 'John',
            'student_last_name' => 'Doe',
            'gender' => 'male',
            'date_of_birth' => '2012-02-03',
            'birth_certificate_number' => 'BC-1234',
            'guardian_name' => 'Jane Doe',
            'guardian_phone' => '+263700000000',
            'guardian_email' => 'parent@example.com',
            'emergency_phone' => '+263711111111',
        ])->assertOk();

        $token = $start->json('data.issued_token');
        $dob = '2012-02-03';

        $this->postJson('/api/admissions/v2/draft/save', [
            'token' => $token,
            'date_of_birth' => $dob,
            'draft_revision' => 0,
            'current_step' => 2,
            'address' => '123 Test Street',
        ])->assertOk()->assertJsonPath('data.draft_revision', 1);

        $this->postJson('/api/admissions/v2/draft/save', [
            'token' => $token,
            'date_of_birth' => $dob,
            'draft_revision' => 0,
            'current_step' => 2,
            'occupation' => 'Teacher',
        ])->assertStatus(409)->assertJsonPath('code', 'REVISION_CONFLICT');
    }

    public function test_v2_submission_requires_mandatory_documents_and_hard_blocks_duplicates_by_birth_cert_and_intake_year(): void
    {
        Storage::fake('public');
        [$yearId, $formId, $catId] = $this->seedAcademic();

        $start = $this->postJson('/api/admissions/v2/draft/start', [
            'application_type' => 'new_intake',
            'academic_year_id' => $yearId,
            'applying_form_id' => $formId,
            'preferred_category_id' => $catId,
            'student_first_name' => 'John',
            'student_last_name' => 'Doe',
            'gender' => 'male',
            'date_of_birth' => '2012-02-03',
            'birth_certificate_number' => 'BC-1234',
            'guardian_name' => 'Jane Doe',
            'guardian_phone' => '+263700000000',
            'guardian_email' => 'parent@example.com',
            'emergency_phone' => '+263711111111',
        ])->assertOk();

        $token = $start->json('data.issued_token');
        $dob = '2012-02-03';

        $this->postJson('/api/admissions/v2/draft/save', [
            'token' => $token,
            'date_of_birth' => $dob,
            'draft_revision' => 0,
            'current_step' => 4,
            'grade7_school' => 'Test School',
            'grade7_results' => 'A',
        ])->assertOk();

        $this->postJson('/api/admissions/v2/submit', [
            'token' => $token,
            'date_of_birth' => $dob,
        ])->assertStatus(422)->assertJsonPath('code', 'IDEMPOTENCY_REQUIRED');

        $this->postJson('/api/admissions/v2/submit', [
            'token' => $token,
            'date_of_birth' => $dob,
        ], ['X-Idempotency-Key' => 'k1'])->assertStatus(422)->assertJsonPath('code', 'MISSING_DOCUMENTS');

        foreach (['birth_certificate', 'passport_photo', 'grade7_report'] as $docType) {
            $this->postJson('/api/admissions/v2/documents/upload', [
                'token' => $token,
                'date_of_birth' => $dob,
                'document_type' => $docType,
                'document' => UploadedFile::fake()->create($docType . '.pdf', 10, 'application/pdf'),
            ])->assertOk();
        }

        $submitted = $this->postJson('/api/admissions/v2/submit', [
            'token' => $token,
            'date_of_birth' => $dob,
        ], ['X-Idempotency-Key' => 'k1'])->assertOk();

        $this->assertNotEmpty($submitted->json('data.issued_token'));
        $this->assertSame('SUBMITTED', $submitted->json('data.lifecycle_state'));

        $this->postJson('/api/admissions/v2/draft/start', [
            'application_type' => 'new_intake',
            'academic_year_id' => $yearId,
            'applying_form_id' => $formId,
            'preferred_category_id' => $catId,
            'student_first_name' => 'John',
            'student_last_name' => 'Doe',
            'gender' => 'male',
            'date_of_birth' => '2012-02-03',
            'birth_certificate_number' => 'BC-1234',
            'guardian_name' => 'Jane Doe',
            'guardian_phone' => '+263700000000',
            'guardian_email' => 'parent@example.com',
            'emergency_phone' => '+263711111111',
        ])->assertStatus(409)->assertJsonPath('code', 'DUPLICATE_SUBMITTED');
    }

    public function test_v2_document_upload_versions_are_immutable_and_supersede_prior_version(): void
    {
        Storage::fake('public');
        [$yearId, $formId, $catId] = $this->seedAcademic();

        $start = $this->postJson('/api/admissions/v2/draft/start', [
            'application_type' => 'new_intake',
            'academic_year_id' => $yearId,
            'applying_form_id' => $formId,
            'preferred_category_id' => $catId,
            'student_first_name' => 'John',
            'student_last_name' => 'Doe',
            'gender' => 'male',
            'date_of_birth' => '2012-02-03',
            'birth_certificate_number' => 'BC-1234',
            'guardian_name' => 'Jane Doe',
            'guardian_phone' => '+263700000000',
            'guardian_email' => 'parent@example.com',
            'emergency_phone' => '+263711111111',
        ])->assertOk();

        $token = $start->json('data.issued_token');
        $dob = '2012-02-03';

        $r1 = $this->postJson('/api/admissions/v2/documents/upload', [
            'token' => $token,
            'date_of_birth' => $dob,
            'document_type' => 'birth_certificate',
            'document' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
        ])->assertOk();

        $r2 = $this->postJson('/api/admissions/v2/documents/upload', [
            'token' => $token,
            'date_of_birth' => $dob,
            'document_type' => 'birth_certificate',
            'document' => UploadedFile::fake()->create('b.pdf', 10, 'application/pdf'),
        ])->assertOk();

        $this->assertSame(1, (int) $r1->json('data.version'));
        $this->assertSame(2, (int) $r2->json('data.version'));

        $active = DB::table('application_documents')
            ->where('document_type', 'birth_certificate')
            ->whereNull('superseded_at')
            ->count();
        $this->assertSame(1, $active);

        $total = DB::table('application_documents')
            ->where('document_type', 'birth_certificate')
            ->count();
        $this->assertSame(2, $total);
    }
}
