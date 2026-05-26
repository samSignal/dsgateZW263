<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('student_enrollments')) {
            Schema::create('student_enrollments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('academic_year_id');
                $table->unsignedBigInteger('term_id');
                $table->unsignedBigInteger('form_id');
                $table->unsignedBigInteger('stream_id');
                $table->unsignedBigInteger('category_id')->nullable();
                $table->enum('enrollment_status', ['enrolled', 'completed', 'withdrawn', 'transferred', 'supplementary', 'repeated', 'graduated'])->default('enrolled');
                $table->unsignedBigInteger('promoted_from_stream_id')->nullable();
                $table->unsignedBigInteger('promoted_to_stream_id')->nullable();
                $table->boolean('repeated')->default(false);
                $table->boolean('graduated')->default(false);
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->unique(['student_id', 'academic_year_id', 'term_id'], 'student_enrollments_unique_student_term');
                $table->index(['academic_year_id', 'term_id']);
                $table->index(['stream_id', 'form_id']);
                $table->index('category_id');
            });
        }

        if (!Schema::hasTable('progression_rules')) {
            Schema::create('progression_rules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('academic_year_id');
                $table->unsignedBigInteger('form_id')->nullable();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->decimal('minimum_pass_mark', 5, 2)->default(50);
                $table->decimal('promotion_min_average', 5, 2)->nullable();
                $table->unsignedInteger('promotion_max_failed_subjects')->nullable();
                $table->decimal('repeat_below_average', 5, 2)->nullable();
                $table->decimal('supplementary_below_average', 5, 2)->nullable();
                $table->text('required_subject_ids_json')->nullable();
                $table->boolean('withhold_results_on_balance')->default(true);
                $table->decimal('withhold_balance_threshold', 12, 2)->default(0);
                $table->text('gpa_scale_json')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->unique(['academic_year_id', 'form_id', 'category_id'], 'progression_rules_unique_scope');
                $table->index(['academic_year_id']);
                $table->index(['form_id', 'category_id']);
            });
        }

        if (!Schema::hasTable('results_aggregates')) {
            Schema::create('results_aggregates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('academic_year_id');
                $table->unsignedBigInteger('term_id');
                $table->unsignedBigInteger('form_id')->nullable();
                $table->unsignedBigInteger('stream_id')->nullable();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->decimal('term_average', 6, 2)->nullable();
                $table->decimal('gpa', 4, 2)->nullable();
                $table->unsignedInteger('subjects_total')->default(0);
                $table->unsignedInteger('subjects_entered')->default(0);
                $table->unsignedInteger('subjects_passed')->default(0);
                $table->boolean('is_withheld')->default(false);
                $table->timestamp('computed_at')->nullable();
                $table->timestamps();

                $table->unique(['student_id', 'academic_year_id', 'term_id'], 'results_aggregates_unique_student_term');
                $table->index(['academic_year_id', 'term_id']);
                $table->index(['stream_id', 'form_id']);
                $table->index('category_id');
                $table->index('is_withheld');
            });
        }

        if (!Schema::hasTable('transcript_subject_history')) {
            Schema::create('transcript_subject_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('academic_year_id');
                $table->unsignedBigInteger('term_id');
                $table->unsignedBigInteger('form_id')->nullable();
                $table->unsignedBigInteger('stream_id')->nullable();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->unsignedBigInteger('subject_id');
                $table->decimal('subject_average', 6, 2)->nullable();
                $table->string('grade', 10)->nullable();
                $table->decimal('gpa_points', 4, 2)->nullable();
                $table->boolean('is_withheld')->default(false);
                $table->timestamp('computed_at')->nullable();
                $table->timestamps();

                $table->unique(['student_id', 'academic_year_id', 'term_id', 'subject_id'], 'transcript_subject_unique_row');
                $table->index(['academic_year_id', 'term_id']);
                $table->index(['stream_id', 'form_id']);
                $table->index('category_id');
                $table->index('subject_id');
            });
        }

        if (!Schema::hasTable('results_group_aggregates')) {
            Schema::create('results_group_aggregates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('academic_year_id');
                $table->unsignedBigInteger('term_id');
                $table->enum('group_type', ['stream', 'form', 'category', 'subject_stream', 'subject_form', 'subject_category']);
                $table->unsignedBigInteger('group_id')->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->decimal('average', 6, 2)->nullable();
                $table->unsignedInteger('student_count')->default(0);
                $table->timestamp('computed_at')->nullable();
                $table->timestamps();

                $table->unique(['academic_year_id', 'term_id', 'group_type', 'group_id', 'subject_id'], 'results_group_aggregates_unique');
                $table->index(['academic_year_id', 'term_id']);
                $table->index(['group_type', 'group_id']);
                $table->index('subject_id');
            });
        }

        if (!Schema::hasTable('results_rankings')) {
            Schema::create('results_rankings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('academic_year_id');
                $table->unsignedBigInteger('term_id');
                $table->enum('ranking_type', ['stream', 'form', 'category', 'subject_stream', 'subject_form', 'subject_category']);
                $table->unsignedBigInteger('ranking_id')->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->unsignedBigInteger('student_id');
                $table->decimal('score', 6, 2)->nullable();
                $table->unsignedInteger('rank')->nullable();
                $table->boolean('is_withheld')->default(false);
                $table->timestamp('computed_at')->nullable();
                $table->timestamps();

                $table->unique(['academic_year_id', 'term_id', 'ranking_type', 'ranking_id', 'subject_id', 'student_id'], 'results_rankings_unique');
                $table->index(['academic_year_id', 'term_id']);
                $table->index(['ranking_type', 'ranking_id']);
                $table->index('subject_id');
                $table->index('student_id');
            });
        }

        if (!Schema::hasTable('transcript_year_aggregates')) {
            Schema::create('transcript_year_aggregates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('academic_year_id');
                $table->unsignedBigInteger('form_id')->nullable();
                $table->unsignedBigInteger('stream_id')->nullable();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->decimal('year_average', 6, 2)->nullable();
                $table->decimal('gpa', 4, 2)->nullable();
                $table->boolean('is_withheld')->default(false);
                $table->timestamp('computed_at')->nullable();
                $table->timestamps();

                $table->unique(['student_id', 'academic_year_id'], 'transcript_year_unique_student_year');
                $table->index(['academic_year_id']);
                $table->index(['stream_id', 'form_id']);
                $table->index('category_id');
            });
        }

        if (!Schema::hasTable('graduation_readiness')) {
            Schema::create('graduation_readiness', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('academic_year_id');
                $table->unsignedBigInteger('form_id')->nullable();
                $table->unsignedBigInteger('stream_id')->nullable();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->enum('status', ['unknown', 'not_ready', 'ready'])->default('unknown');
                $table->string('reason', 255)->nullable();
                $table->timestamp('computed_at')->nullable();
                $table->timestamps();

                $table->unique(['student_id', 'academic_year_id'], 'graduation_readiness_unique_student_year');
                $table->index(['academic_year_id']);
                $table->index(['stream_id', 'form_id']);
                $table->index('category_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('graduation_readiness');
        Schema::dropIfExists('transcript_year_aggregates');
        Schema::dropIfExists('results_rankings');
        Schema::dropIfExists('results_group_aggregates');
        Schema::dropIfExists('transcript_subject_history');
        Schema::dropIfExists('results_aggregates');
        Schema::dropIfExists('progression_rules');
        Schema::dropIfExists('student_enrollments');
    }
};
