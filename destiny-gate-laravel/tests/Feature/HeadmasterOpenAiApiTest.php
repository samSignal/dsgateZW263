<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HeadmasterOpenAiApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createMinimalSchema();
    }

    public function test_headmaster_can_fetch_ai_insights(): void
    {
        config([
            'services.openai.key' => 'test-openai-key',
            'services.openai.base_url' => 'https://api.openai.com/v1',
            'services.openai.model' => 'gpt-4o-mini',
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'summary' => 'Attendance is stable, but application processing needs closer follow-up.',
                            'risks' => [
                                'Pending applications could pile up before term opening.',
                                'Behaviour case volume may need closer weekly review.',
                                'Fee collection should be checked against target.',
                            ],
                            'recommended_actions' => [
                                'Review admissions turnaround with the admin office.',
                                'Run a weekly discipline case review meeting.',
                                'Share a finance follow-up plan with the bursar.',
                            ],
                            'confidence_note' => 'This guidance is based on current dashboard indicators only.',
                        ]),
                    ],
                ]],
            ]),
        ]);

        $user = User::factory()->create(['role' => 'headmaster']);
        Sanctum::actingAs($user);

        $this->postJson('/api/headmaster/ai/insights', [
            'focus' => 'admissions and discipline',
        ])
            ->assertOk()
            ->assertJsonPath('summary', 'Attendance is stable, but application processing needs closer follow-up.')
            ->assertJsonPath('risks.0', 'Pending applications could pile up before term opening.')
            ->assertJsonPath('recommended_actions.2', 'Share a finance follow-up plan with the bursar.')
            ->assertJsonPath('model', 'gpt-4o-mini');
    }

    public function test_headmaster_can_generate_ai_announcement_draft(): void
    {
        config([
            'services.openai.key' => 'test-openai-key',
            'services.openai.base_url' => 'https://api.openai.com/v1',
            'services.openai.model' => 'gpt-4o-mini',
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'title' => 'Mid-Term Academic Review Meeting',
                            'content' => "Dear Parents,\n\nWe will hold a mid-term academic review meeting on Friday at 10:00 AM in the school hall.\n\nPlease attend on time and bring any questions about learner progress.",
                            'key_points' => [
                                'Meeting starts at 10:00 AM.',
                                'Venue is the school hall.',
                                'Parents should bring progress questions.',
                            ],
                        ]),
                    ],
                ]],
            ]),
        ]);

        $user = User::factory()->create(['role' => 'headmaster']);
        Sanctum::actingAs($user);

        $this->postJson('/api/headmaster/ai/announcements/draft', [
            'topic' => 'Mid-term academic review meeting',
            'audience' => 'parents',
            'tone' => 'warm and professional',
            'objective' => 'Invite parents and explain the purpose of the meeting.',
        ])
            ->assertOk()
            ->assertJsonPath('title', 'Mid-Term Academic Review Meeting')
            ->assertJsonPath('key_points.1', 'Venue is the school hall.')
            ->assertJsonPath('model', 'gpt-4o-mini');
    }

    public function test_ai_endpoints_return_service_unavailable_when_openai_is_not_configured(): void
    {
        config([
            'services.openai.key' => null,
        ]);

        $user = User::factory()->create(['role' => 'headmaster']);
        Sanctum::actingAs($user);

        $this->postJson('/api/headmaster/ai/insights')
            ->assertStatus(503)
            ->assertJsonPath('message', 'OpenAI is not configured. Set OPENAI_API_KEY in your environment.');
    }

    private function createMinimalSchema(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('role')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('students')) {
            Schema::create('students', function (Blueprint $table): void {
                $table->id();
                $table->string('status')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('staff')) {
            Schema::create('staff', function (Blueprint $table): void {
                $table->id();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table): void {
                $table->id();
                $table->date('payment_date')->nullable();
                $table->decimal('amount', 12, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('behaviour_records')) {
            Schema::create('behaviour_records', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('student_id')->nullable();
                $table->date('issue_date')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('applications')) {
            Schema::create('applications', function (Blueprint $table): void {
                $table->id();
                $table->string('status')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('classes')) {
            Schema::create('classes', function (Blueprint $table): void {
                $table->id();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $table): void {
                $table->id();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->string('title')->nullable();
                $table->text('content')->nullable();
                $table->string('audience')->nullable();
                $table->timestamps();
            });
        }
    }
}
