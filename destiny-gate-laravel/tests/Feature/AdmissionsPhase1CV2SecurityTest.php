<?php

namespace Tests\Feature;

use App\Mail\AdmissionsMagicLinkMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdmissionsPhase1CV2SecurityTest extends TestCase
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

        DB::table('admission_intakes')->insert([
            'academic_year_id' => $yearId,
            'is_active' => true,
            'opens_at' => now()->subDay(),
            'closes_at' => now()->addDays(30),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$yearId, $formId, $catId];
    }

    private function parseCodeFromMail(AdmissionsMagicLinkMail $m): string
    {
        $url = $m->buttonUrl;
        $parts = parse_url($url);
        parse_str($parts['query'] ?? '', $qs);
        return (string) ($qs['code'] ?? '');
    }

    public function test_stepup_magic_link_is_one_time_and_grants_scoped_stepup(): void
    {
        Mail::fake();
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

        $token = (string) $start->json('data.issued_token');
        $dob = '2012-02-03';

        $sess = $this->postJson('/api/admissions/v2/session/start', [
            'token' => $token,
            'date_of_birth' => $dob,
        ])->assertOk();

        $sessionToken = (string) $sess->json('data.session_token');

        $this->postJson('/api/admissions/v2/verification/stepup/request', ['action' => 'change_identity'], [
            'X-Admissions-Session' => $sessionToken,
        ])->assertOk();

        Mail::assertSent(AdmissionsMagicLinkMail::class, 1);
        $mail = Mail::sent(AdmissionsMagicLinkMail::class)->first();
        $code = $this->parseCodeFromMail($mail);
        $this->assertNotEmpty($code);

        $consumed = $this->postJson('/api/admissions/v2/verification/consume', ['code' => $code])->assertOk();
        $this->assertSame('stepup', $consumed->json('data.type'));
        $this->assertContains('identity', (array) $consumed->json('data.session_stepup_scopes'));

        $this->postJson('/api/admissions/v2/verification/consume', ['code' => $code])
            ->assertStatus(410)
            ->assertJsonPath('code', 'INVALID_OR_EXPIRED');
    }

    public function test_sensitive_identity_change_requires_stepup_and_rotates_access_token(): void
    {
        Mail::fake();
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

        $appId = (int) $start->json('data.application_id');
        $token = (string) $start->json('data.issued_token');
        $dob = '2012-02-03';

        $sess = $this->postJson('/api/admissions/v2/session/start', [
            'token' => $token,
            'date_of_birth' => $dob,
        ])->assertOk();
        $sessionToken = (string) $sess->json('data.session_token');

        $this->postJson('/api/admissions/v2/draft/save', [
            'draft_revision' => 0,
            'current_step' => 2,
            'student_first_name' => 'Johnny',
        ], [
            'X-Admissions-Session' => $sessionToken,
        ])->assertStatus(403)->assertJsonPath('code', 'STEPUP_REQUIRED');

        $this->postJson('/api/admissions/v2/verification/stepup/request', ['action' => 'change_identity'], [
            'X-Admissions-Session' => $sessionToken,
        ])->assertOk();

        $mail = Mail::sent(AdmissionsMagicLinkMail::class)->last();
        $code = $this->parseCodeFromMail($mail);
        $this->postJson('/api/admissions/v2/verification/consume', ['code' => $code])->assertOk();

        $saved = $this->postJson('/api/admissions/v2/draft/save', [
            'draft_revision' => 0,
            'current_step' => 2,
            'student_first_name' => 'Johnny',
        ], [
            'X-Admissions-Session' => $sessionToken,
        ])->assertOk();

        $newToken = (string) $saved->json('data.issued_token');
        $this->assertNotEmpty($newToken);
        $this->assertNotSame($token, $newToken);

        $oldHash = hash_hmac('sha256', strtoupper($token), (string) config('app.key'));
        $oldRevoked = DB::table('admission_application_tokens')
            ->where('application_id', $appId)
            ->where('token_hash', $oldHash)
            ->value('revoked_at');
        $this->assertNotNull($oldRevoked);
    }

    public function test_recovery_flow_sends_magic_link_and_rotates_token(): void
    {
        Mail::fake();
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

        $oldToken = (string) $start->json('data.issued_token');

        $this->postJson('/api/admissions/v2/recovery/request', [
            'academic_year_id' => $yearId,
            'birth_certificate_number' => 'BC-1234',
            'guardian_email' => 'parent@example.com',
        ])->assertOk();

        Mail::assertSent(AdmissionsMagicLinkMail::class, 1);
        $mail = Mail::sent(AdmissionsMagicLinkMail::class)->first();
        $code = $this->parseCodeFromMail($mail);
        $this->assertNotEmpty($code);

        $res = $this->postJson('/api/admissions/v2/verification/consume', ['code' => $code])->assertOk();
        $this->assertSame('recovery', $res->json('data.type'));
        $this->assertNotEmpty($res->json('data.session_token'));

        $newToken = (string) $res->json('data.issued_token');
        $this->assertNotSame($oldToken, $newToken);
    }

    public function test_intake_close_archives_draft_on_read_only_get(): void
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

        $token = (string) $start->json('data.issued_token');
        $dob = '2012-02-03';

        DB::table('admission_intakes')->where('academic_year_id', $yearId)->update([
            'closes_at' => now()->subMinute(),
            'updated_at' => now(),
        ]);

        $got = $this->postJson('/api/admissions/v2/draft/get', [
            'token' => $token,
            'date_of_birth' => $dob,
        ])->assertOk();

        $this->assertSame('archived_intake_closed', $got->json('data.application.archived_reason'));
    }

    public function test_intake_close_blocks_new_draft_creation(): void
    {
        [$yearId, $formId, $catId] = $this->seedAcademic();
        DB::table('admission_intakes')->where('academic_year_id', $yearId)->update([
            'closes_at' => now()->subMinute(),
            'updated_at' => now(),
        ]);

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
        ])->assertStatus(409)->assertJsonPath('code', 'INTAKE_CLOSED');
    }

    public function test_suspicious_token_verification_lockout_is_enforced(): void
    {
        config([
            'admissions.suspicious.token_verify_fail_threshold' => 2,
            'admissions.suspicious.lockout_minutes' => 15,
        ]);

        $this->postJson('/api/admissions/v2/session/start', [
            'token' => 'DGI-INVALID',
            'date_of_birth' => '2012-02-03',
        ])->assertStatus(403)->assertJsonPath('code', 'UNAUTHORIZED');

        $this->postJson('/api/admissions/v2/session/start', [
            'token' => 'DGI-INVALID',
            'date_of_birth' => '2012-02-03',
        ])->assertStatus(429)->assertJsonPath('code', 'LOCKED');
    }

    public function test_inactivity_archival_command_archives_inactive_drafts(): void
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

        $appId = (int) $start->json('data.application_id');
        DB::table('admission_applications')->where('id', $appId)->update([
            'last_activity_at' => now()->subDays(120),
            'updated_at' => now()->subDays(120),
        ]);

        $this->artisan('admissions:archive-inactive-drafts')->assertExitCode(0);

        $row = DB::table('admission_applications')->where('id', $appId)->first();
        $this->assertNotNull($row->archived_at);
        $this->assertSame('archived_inactive', $row->archived_reason);
    }

    public function test_archived_drafts_are_blocked_for_public_editing_and_document_uploads(): void
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

        $token = (string) $start->json('data.issued_token');
        $dob = '2012-02-03';
        $appId = (int) $start->json('data.application_id');

        DB::table('admission_applications')->where('id', $appId)->update([
            'archived_at' => now(),
            'archived_reason' => 'archived_inactive',
            'lifecycle_state' => 'DRAFT_EXPIRED_ARCHIVED',
            'updated_at' => now(),
        ]);

        $this->postJson('/api/admissions/v2/draft/save', [
            'token' => $token,
            'date_of_birth' => $dob,
            'draft_revision' => 0,
            'current_step' => 2,
            'address' => '123 Test Street',
        ])->assertStatus(409)->assertJsonPath('code', 'DRAFT_ARCHIVED');

        $this->postJson('/api/admissions/v2/documents/upload', [
            'token' => $token,
            'date_of_birth' => $dob,
            'document_type' => 'birth_certificate',
            'document' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
        ])->assertStatus(409)->assertJsonPath('code', 'DRAFT_ARCHIVED');
    }
}
