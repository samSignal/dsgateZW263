<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admission_document_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('admission_applications')->onDelete('cascade');
            $table->foreignId('document_id')->constrained('application_documents')->onDelete('cascade');
            $table->string('document_type', 60)->index();
            $table->unsignedInteger('document_version')->default(1);
            $table->enum('verification_status', ['pending', 'verified', 'rejected', 'reupload_requested'])->default('pending')->index();
            $table->unsignedBigInteger('reviewed_by')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable()->index();
            $table->string('rejection_reason', 500)->nullable();
            $table->text('verification_notes')->nullable();
            $table->timestamps();

            $table->unique(['document_id'], 'adr_document_unique');
            $table->index(['application_id', 'document_type'], 'adr_app_type_idx');
        });

        Schema::create('admission_office_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('admission_applications')->onDelete('cascade');
            $table->enum('visibility', ['internal', 'applicant'])->index();
            $table->string('template_key', 80)->nullable()->index();
            $table->text('message');
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('admission_duplicate_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('canonical_application_id')->index();
            $table->unsignedBigInteger('related_application_id')->index();
            $table->enum('status', ['flagged', 'merged', 'invalid'])->index();
            $table->string('reason', 1000)->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['canonical_application_id', 'related_application_id'], 'adl_canonical_related_unique');
        });

        Schema::table('admission_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('admission_applications', 'assigned_reviewer_id')) {
                $table->unsignedBigInteger('assigned_reviewer_id')->nullable()->after('reviewed_by');
                $table->index('assigned_reviewer_id', 'aa_assigned_reviewer_idx');
            }
            if (!Schema::hasColumn('admission_applications', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable()->after('assigned_reviewer_id');
            }
            if (!Schema::hasColumn('admission_applications', 'review_started_at')) {
                $table->timestamp('review_started_at')->nullable()->after('assigned_at');
            }
            if (!Schema::hasColumn('admission_applications', 'review_completed_at')) {
                $table->timestamp('review_completed_at')->nullable()->after('review_started_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admission_applications', function (Blueprint $table) {
            foreach (['review_completed_at', 'review_started_at', 'assigned_at', 'assigned_reviewer_id'] as $col) {
                if (Schema::hasColumn('admission_applications', $col)) {
                    if ($col === 'assigned_reviewer_id') $table->dropIndex('aa_assigned_reviewer_idx');
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('admission_duplicate_links');
        Schema::dropIfExists('admission_office_notes');
        Schema::dropIfExists('admission_document_reviews');
    }
};

