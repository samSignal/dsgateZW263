<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdmissionsPhase1DV2OfficeWorkflowTest extends TestCase
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

    private function makeOfficeUser(array $permissions): User
    {
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        Role::firstOrCreate(['name' => 'admissions_office', 'guard_name' => 'web']);

        $user = User::factory()->create([
            'role' => 'admin',
        ]);
        $user->syncRoles(['admissions_office']);
        $user->givePermissionTo($permissions);
        return $user;
    }

    private function createSubmittedApplication(int $yearId, int $formId, int $catId): int
    {
        $suffix = str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT) . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
        $now = now();
        return DB::table('admission_applications')->insertGetId([
            'application_number' => 'APP-2026-' . $suffix,
            'tracking_token' => 'V2-' . $suffix,
            'application_type' => 'new_intake',
            'academic_year_id' => $yearId,
            'term_id' => null,
            'applying_form_id' => $formId,
            'preferred_category_id' => $catId,
            'student_first_name' => 'John',
            'student_last_name' => 'Doe',
            'gender' => 'male',
            'date_of_birth' => '2012-02-03',
            'birth_certificate_number' => 'BC-1234',
            'birth_certificate_number_normalized' => 'BC1234',
            'student_national_id' => null,
            'guardian_name' => 'Jane Doe',
            'guardian_phone' => '+263700000000',
            'guardian_email' => 'parent@example.com',
            'emergency_phone' => '+263711111111',
            'address' => null,
            'occupation' => null,
            'status' => 'submitted',
            'lifecycle_state' => 'SUBMITTED',
            'current_step' => 6,
            'draft_revision' => 1,
            'is_submitted' => true,
            'progress_percentage' => 15,
            'completion_percentage' => 100,
            'status_updated_at' => $now,
            'submitted_at' => $now,
            'draft_last_saved_at' => $now,
            'last_activity_at' => $now,
            'expires_at' => null,
            'archived_at' => null,
            'locked_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function test_reviewer_claim_concurrency_is_enforced(): void
    {
        [$yearId, $formId, $catId] = $this->seedAcademic();
        $appId = $this->createSubmittedApplication($yearId, $formId, $catId);

        $p = ['admissions.office.review_view', 'admissions.office.review_claim', 'admissions.office.queue_view'];
        $u1 = $this->makeOfficeUser($p);
        $u2 = $this->makeOfficeUser($p);

        $this->actingAs($u1, 'sanctum')
            ->postJson("/api/admissions-office/v2/review/{$appId}/claim")
            ->assertOk();

        $this->actingAs($u2, 'sanctum')
            ->postJson("/api/admissions-office/v2/review/{$appId}/claim")
            ->assertStatus(409)
            ->assertJsonPath('code', 'ALREADY_CLAIMED');
    }

    public function test_accept_requires_verified_mandatory_documents_and_decision_permission(): void
    {
        Storage::fake('public');
        [$yearId, $formId, $catId] = $this->seedAcademic();
        $appId = $this->createSubmittedApplication($yearId, $formId, $catId);

        $docIds = [];
        foreach (['birth_certificate', 'passport_photo', 'grade7_report'] as $i => $type) {
            $docIds[] = DB::table('application_documents')->insertGetId([
                'application_id' => $appId,
                'document_type' => $type,
                'file_name' => $type . '.pdf',
                'file_path' => UploadedFile::fake()->create($type . '.pdf', 10, 'application/pdf')->store('admissions/test', 'public'),
                'version' => 1,
                'uploaded_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $reviewer = $this->makeOfficeUser(['admissions.office.review_view', 'admissions.office.transition', 'admissions.office.docs_review']);

        $this->actingAs($reviewer, 'sanctum')
            ->postJson("/api/admissions-office/v2/review/{$appId}/transition", [
                'to_state' => 'ACCEPTED',
                'reason' => 'Meets requirements',
            ])->assertStatus(403)->assertJsonPath('code', 'FORBIDDEN');

        $senior = $this->makeOfficeUser(['admissions.office.review_view', 'admissions.office.transition', 'admissions.office.docs_review', 'admissions.office.decision']);

        $this->actingAs($senior, 'sanctum')
            ->postJson("/api/admissions-office/v2/review/{$appId}/transition", [
                'to_state' => 'UNDER_REVIEW',
                'reason' => 'Review started',
            ])->assertOk();

        $this->actingAs($senior, 'sanctum')
            ->postJson("/api/admissions-office/v2/review/{$appId}/transition", [
                'to_state' => 'READY_FOR_DECISION',
                'reason' => 'Ready checkpoint',
            ])->assertOk();

        $this->actingAs($senior, 'sanctum')
            ->postJson("/api/admissions-office/v2/review/{$appId}/transition", [
                'to_state' => 'ACCEPTED',
                'reason' => 'Meets requirements',
            ])->assertStatus(409)->assertJsonPath('code', 'DOCS_NOT_VERIFIED');

        foreach ($docIds as $docId) {
            $this->actingAs($senior, 'sanctum')
                ->postJson("/api/admissions-office/v2/documents/{$docId}/verify")
                ->assertOk();
        }

        $this->actingAs($senior, 'sanctum')
            ->postJson("/api/admissions-office/v2/review/{$appId}/transition", [
                'to_state' => 'ACCEPTED',
                'reason' => 'Meets requirements',
            ])->assertOk()->assertJsonPath('data.to', 'ACCEPTED');

        $row = DB::table('admission_applications')->where('id', $appId)->first();
        $this->assertSame('accepted', (string) $row->status);
        $this->assertSame('ACCEPTED', (string) $row->lifecycle_state);
    }

    public function test_document_reupload_request_transitions_application_to_documents_required(): void
    {
        Storage::fake('public');
        [$yearId, $formId, $catId] = $this->seedAcademic();
        $appId = $this->createSubmittedApplication($yearId, $formId, $catId);

        $docId = DB::table('application_documents')->insertGetId([
            'application_id' => $appId,
            'document_type' => 'birth_certificate',
            'file_name' => 'birth.pdf',
            'file_path' => UploadedFile::fake()->create('birth.pdf', 10, 'application/pdf')->store('admissions/test', 'public'),
            'version' => 1,
            'uploaded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = $this->makeOfficeUser(['admissions.office.review_view', 'admissions.office.transition', 'admissions.office.docs_review', 'admissions.office.messages']);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/admissions-office/v2/documents/{$docId}/request-reupload", [
                'reason' => 'Image is unclear',
                'notes' => 'Please upload a clearer scan',
                'notify_applicant' => true,
            ])->assertOk();

        $row = DB::table('admission_applications')->where('id', $appId)->first();
        $this->assertSame('DOCUMENTS_REQUIRED', (string) $row->lifecycle_state);
    }

    public function test_ready_for_decision_is_blocked_when_document_requests_exist(): void
    {
        Storage::fake('public');
        [$yearId, $formId, $catId] = $this->seedAcademic();
        $appId = $this->createSubmittedApplication($yearId, $formId, $catId);

        $docId = DB::table('application_documents')->insertGetId([
            'application_id' => $appId,
            'document_type' => 'birth_certificate',
            'file_name' => 'birth.pdf',
            'file_path' => UploadedFile::fake()->create('birth.pdf', 10, 'application/pdf')->store('admissions/test', 'public'),
            'version' => 1,
            'uploaded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = $this->makeOfficeUser(['admissions.office.review_view', 'admissions.office.transition', 'admissions.office.docs_review', 'admissions.office.override']);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/admissions-office/v2/review/{$appId}/transition", [
                'to_state' => 'UNDER_REVIEW',
                'reason' => 'Start review',
            ])->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/admissions-office/v2/documents/{$docId}/request-reupload", [
                'reason' => 'Illegible',
                'notes' => 'Please reupload',
                'notify_applicant' => false,
            ])->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/admissions-office/v2/review/{$appId}/transition", [
                'to_state' => 'UNDER_REVIEW',
                'reason' => 'Return to review',
                'override' => true,
            ])->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/admissions-office/v2/review/{$appId}/transition", [
                'to_state' => 'READY_FOR_DECISION',
                'reason' => 'Attempt ready',
            ])->assertStatus(409)->assertJsonPath('code', 'DOCS_PENDING');
    }

    public function test_reject_and_waitlist_require_reason(): void
    {
        [$yearId, $formId, $catId] = $this->seedAcademic();
        $appId = $this->createSubmittedApplication($yearId, $formId, $catId);

        $user = $this->makeOfficeUser(['admissions.office.review_view', 'admissions.office.transition', 'admissions.office.decision']);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/admissions-office/v2/review/{$appId}/transition", [
                'to_state' => 'REJECTED',
            ])->assertStatus(422);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/admissions-office/v2/review/{$appId}/transition", [
                'to_state' => 'WAITLISTED',
            ])->assertStatus(422);
    }

    public function test_merge_preserves_document_verifications_for_canonical_acceptance(): void
    {
        Storage::fake('public');
        [$yearId, $formId, $catId] = $this->seedAcademic();
        $canonicalId = $this->createSubmittedApplication($yearId, $formId, $catId);
        $relatedId = $this->createSubmittedApplication($yearId, $formId, $catId);

        $docIds = [];
        foreach (['birth_certificate', 'passport_photo', 'grade7_report'] as $type) {
            $docIds[] = DB::table('application_documents')->insertGetId([
                'application_id' => $relatedId,
                'document_type' => $type,
                'file_name' => $type . '.pdf',
                'file_path' => UploadedFile::fake()->create($type . '.pdf', 10, 'application/pdf')->store('admissions/test', 'public'),
                'version' => 1,
                'uploaded_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $senior = $this->makeOfficeUser(['admissions.office.review_view', 'admissions.office.transition', 'admissions.office.docs_review', 'admissions.office.decision', 'admissions.office.duplicates']);

        foreach ($docIds as $docId) {
            $this->actingAs($senior, 'sanctum')
                ->postJson("/api/admissions-office/v2/documents/{$docId}/verify")
                ->assertOk();
        }

        $this->actingAs($senior, 'sanctum')
            ->postJson('/api/admissions-office/v2/duplicates/link', [
                'canonical_application_id' => $canonicalId,
                'related_application_id' => $relatedId,
                'status' => 'merged',
                'reason' => 'Same learner',
            ])->assertOk();

        $this->actingAs($senior, 'sanctum')
            ->postJson("/api/admissions-office/v2/review/{$canonicalId}/transition", [
                'to_state' => 'UNDER_REVIEW',
                'reason' => 'Start review',
            ])->assertOk();

        $this->actingAs($senior, 'sanctum')
            ->postJson("/api/admissions-office/v2/review/{$canonicalId}/transition", [
                'to_state' => 'READY_FOR_DECISION',
                'reason' => 'Docs verified via merge',
            ])->assertOk();

        $this->actingAs($senior, 'sanctum')
            ->postJson("/api/admissions-office/v2/review/{$canonicalId}/transition", [
                'to_state' => 'ACCEPTED',
                'reason' => 'Decision',
            ])->assertOk()->assertJsonPath('data.to', 'ACCEPTED');
    }

    public function test_verified_documents_are_locked_from_further_review_actions(): void
    {
        Storage::fake('public');
        [$yearId, $formId, $catId] = $this->seedAcademic();
        $appId = $this->createSubmittedApplication($yearId, $formId, $catId);

        $docId = DB::table('application_documents')->insertGetId([
            'application_id' => $appId,
            'document_type' => 'birth_certificate',
            'file_name' => 'birth.pdf',
            'file_path' => UploadedFile::fake()->create('birth.pdf', 10, 'application/pdf')->store('admissions/test', 'public'),
            'version' => 1,
            'uploaded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = $this->makeOfficeUser(['admissions.office.review_view', 'admissions.office.docs_review']);

        $this->actingAs($user, 'sanctum')->postJson("/api/admissions-office/v2/documents/{$docId}/verify")->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/admissions-office/v2/documents/{$docId}/reject", [
                'reason' => 'Try change',
            ])->assertStatus(409)->assertJsonPath('code', 'LOCKED');
    }

    public function test_manager_override_can_reopen_archived_and_force_transition(): void
    {
        [$yearId, $formId, $catId] = $this->seedAcademic();
        $appId = $this->createSubmittedApplication($yearId, $formId, $catId);

        DB::table('admission_applications')->where('id', $appId)->update([
            'lifecycle_state' => 'ARCHIVED',
            'archived_at' => now(),
            'archived_reason' => 'manual_archive',
        ]);

        $manager = $this->makeOfficeUser(['admissions.office.review_view', 'admissions.office.transition', 'admissions.office.decision', 'admissions.office.override']);

        $this->actingAs($manager, 'sanctum')
            ->postJson("/api/admissions-office/v2/review/{$appId}/transition", [
                'to_state' => 'UNDER_REVIEW',
                'reason' => 'Reopen for review',
            ])->assertStatus(409)->assertJsonPath('code', 'ARCHIVED');

        $this->actingAs($manager, 'sanctum')
            ->postJson("/api/admissions-office/v2/review/{$appId}/transition", [
                'to_state' => 'UNDER_REVIEW',
                'reason' => 'Reopen for review',
                'override' => true,
            ])->assertOk()->assertJsonPath('data.to', 'UNDER_REVIEW');

        $this->actingAs($manager, 'sanctum')
            ->postJson("/api/admissions-office/v2/review/{$appId}/transition", [
                'to_state' => 'ACCEPTED',
                'reason' => 'Force decision',
                'override' => true,
                'force' => true,
            ])->assertOk()->assertJsonPath('data.to', 'ACCEPTED');

        $this->assertDatabaseHas('admission_audit_events', [
            'application_id' => $appId,
            'event_type' => 'admissions.office.override_performed',
        ]);
    }

    public function test_duplicate_invalid_becomes_immutable_and_blocks_public_document_upload(): void
    {
        Storage::fake('public');
        [$yearId, $formId, $catId] = $this->seedAcademic();
        $appId = $this->createSubmittedApplication($yearId, $formId, $catId);
        $canonId = $this->createSubmittedApplication($yearId, $formId, $catId);

        $user = $this->makeOfficeUser(['admissions.office.duplicates', 'admissions.office.review_view']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/admissions-office/v2/duplicates/link', [
                'canonical_application_id' => $canonId,
                'related_application_id' => $appId,
                'status' => 'invalid',
                'reason' => 'Duplicate submission',
            ])->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/admissions-office/v2/review/{$appId}/transition", [
                'to_state' => 'UNDER_REVIEW',
                'reason' => 'Attempt change',
            ])->assertStatus(403);

        $token = 'DGI-TESTXX';
        DB::table('admission_application_tokens')->insert([
            'application_id' => $appId,
            'purpose' => 'access',
            'token_hash' => hash_hmac('sha256', strtoupper($token), (string) config('app.key')),
            'token_last4' => 'TXX',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/admissions/v2/session/start', [
            'token' => $token,
            'date_of_birth' => '2012-02-03',
        ])->assertOk();

        $this->postJson('/api/admissions/v2/documents/upload', [
            'token' => $token,
            'date_of_birth' => '2012-02-03',
            'document_type' => 'birth_certificate',
            'document' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
        ])->assertStatus(409)->assertJsonPath('code', 'UPLOAD_NOT_ALLOWED');
    }

    public function test_public_draft_save_and_submit_are_blocked_when_duplicate_invalid(): void
    {
        [$yearId, $formId, $catId] = $this->seedAcademic();
        $now = now();
        $appId = DB::table('admission_applications')->insertGetId([
            'application_number' => 'APP-2026-00999',
            'tracking_token' => 'V2-LEGACY-2',
            'application_type' => 'new_intake',
            'academic_year_id' => $yearId,
            'term_id' => null,
            'applying_form_id' => $formId,
            'preferred_category_id' => $catId,
            'student_first_name' => 'Jane',
            'student_last_name' => 'Roe',
            'gender' => 'female',
            'date_of_birth' => '2012-02-03',
            'birth_certificate_number' => 'BC-9999',
            'birth_certificate_number_normalized' => 'BC9999',
            'guardian_name' => 'Guardian',
            'guardian_phone' => '+263700000000',
            'guardian_email' => 'guardian@example.com',
            'status' => 'draft',
            'lifecycle_state' => 'DUPLICATE_INVALID',
            'current_step' => 2,
            'draft_revision' => 0,
            'is_submitted' => false,
            'progress_percentage' => 0,
            'completion_percentage' => 0,
            'status_updated_at' => $now,
            'submitted_at' => null,
            'draft_last_saved_at' => $now,
            'last_activity_at' => $now,
            'expires_at' => null,
            'archived_at' => null,
            'locked_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $token = 'DGI-TESTYY';
        DB::table('admission_application_tokens')->insert([
            'application_id' => $appId,
            'purpose' => 'access',
            'token_hash' => hash_hmac('sha256', strtoupper($token), (string) config('app.key')),
            'token_last4' => 'TYY',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->postJson('/api/admissions/v2/draft/save', [
            'token' => $token,
            'date_of_birth' => '2012-02-03',
            'draft_revision' => 0,
            'current_step' => 2,
            'student_first_name' => 'Jane',
        ])->assertStatus(409)->assertJsonPath('code', 'NOT_EDITABLE');

        $this->postJson('/api/admissions/v2/submit', [
            'token' => $token,
            'date_of_birth' => '2012-02-03',
        ])->assertStatus(409)->assertJsonPath('code', 'NOT_SUBMITTABLE');
    }
}
