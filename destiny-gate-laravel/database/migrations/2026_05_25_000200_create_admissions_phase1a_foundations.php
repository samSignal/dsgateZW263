<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admission_intakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('restrict');
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['academic_year_id']);
        });

        Schema::table('admission_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('admission_applications', 'preferred_category_id')) {
                $table->foreignId('preferred_category_id')->nullable()->after('applying_form_id')->constrained('categories')->onDelete('restrict');
            }
            if (!Schema::hasColumn('admission_applications', 'birth_certificate_number_normalized')) {
                $table->string('birth_certificate_number_normalized', 80)->nullable()->after('birth_certificate_number');
                $table->index(['academic_year_id', 'birth_certificate_number_normalized'], 'aa_year_birthcert_norm_idx');
            }
            if (!Schema::hasColumn('admission_applications', 'lifecycle_state')) {
                $table->string('lifecycle_state', 40)->nullable()->after('status');
                $table->index('lifecycle_state');
            }
            if (!Schema::hasColumn('admission_applications', 'draft_revision')) {
                $table->unsignedInteger('draft_revision')->default(0)->after('current_step');
            }
            if (!Schema::hasColumn('admission_applications', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable()->after('draft_last_saved_at');
            }
            if (!Schema::hasColumn('admission_applications', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('last_activity_at');
            }
            if (!Schema::hasColumn('admission_applications', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('expires_at');
            }
            if (!Schema::hasColumn('admission_applications', 'locked_at')) {
                $table->timestamp('locked_at')->nullable()->after('archived_at');
            }
        });

        Schema::create('admission_application_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('admission_applications')->onDelete('cascade');
            $table->enum('purpose', ['access', 'stepup', 'recovery'])->default('access')->index();
            $table->string('token_hash', 80)->unique();
            $table->string('token_last4', 10)->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->string('created_ip', 60)->nullable();
            $table->string('created_user_agent', 500)->nullable();
            $table->timestamps();
            $table->index(['application_id', 'purpose', 'revoked_at'], 'aat_app_purpose_active_idx');
        });

        Schema::create('admission_application_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('admission_applications')->onDelete('cascade');
            $table->foreignId('intake_academic_year_id')->constrained('academic_years')->onDelete('restrict');
            $table->string('birth_certificate_number_normalized', 80);
            $table->string('idempotency_key', 120);
            $table->json('payload');
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();
            $table->unique(['intake_academic_year_id', 'birth_certificate_number_normalized'], 'aas_year_birthcert_unique');
            $table->unique(['application_id', 'idempotency_key'], 'aas_app_idem_unique');
        });

        Schema::create('admission_audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->nullable()->constrained('admission_applications')->onDelete('set null');
            $table->enum('actor_type', ['applicant', 'admissions', 'system'])->index();
            $table->unsignedBigInteger('actor_user_id')->nullable()->index();
            $table->string('event_type', 120)->index();
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info')->index();
            $table->string('ip', 60)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['application_id', 'created_at'], 'aae_app_created_idx');
        });

        Schema::table('application_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('application_documents', 'version')) {
                $table->unsignedInteger('version')->default(1)->after('file_path');
                $table->timestamp('superseded_at')->nullable()->after('version');
                $table->unsignedBigInteger('superseded_by')->nullable()->after('superseded_at');
                $table->index(['application_id', 'document_type', 'superseded_at'], 'adoc_app_type_active_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('application_documents', function (Blueprint $table) {
            if (Schema::hasColumn('application_documents', 'version')) {
                $table->dropIndex('adoc_app_type_active_idx');
                $table->dropColumn(['version', 'superseded_at', 'superseded_by']);
            }
        });

        Schema::dropIfExists('admission_audit_events');
        Schema::dropIfExists('admission_application_submissions');
        Schema::dropIfExists('admission_application_tokens');

        Schema::table('admission_applications', function (Blueprint $table) {
            if (Schema::hasColumn('admission_applications', 'preferred_category_id')) {
                $table->dropConstrainedForeignId('preferred_category_id');
            }
            if (Schema::hasColumn('admission_applications', 'birth_certificate_number_normalized')) {
                $table->dropIndex('aa_year_birthcert_norm_idx');
                $table->dropColumn('birth_certificate_number_normalized');
            }
            foreach (['lifecycle_state', 'draft_revision', 'last_activity_at', 'expires_at', 'archived_at', 'locked_at'] as $col) {
                if (Schema::hasColumn('admission_applications', $col)) {
                    if ($col === 'lifecycle_state') $table->dropIndex([$col]);
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('admission_intakes');
    }
};

