<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->string('report_number', 30)->unique();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('form_id');
            $table->unsignedBigInteger('stream_id');
            $table->decimal('overall_average', 5, 2)->nullable();
            $table->string('overall_grade', 20)->nullable();
            $table->unsignedInteger('overall_points')->nullable();
            $table->unsignedInteger('class_position')->nullable();
            $table->unsignedInteger('stream_total_students')->nullable();
            $table->decimal('attendance_percentage', 5, 2)->nullable();
            $table->string('conduct_grade', 30)->nullable();
            $table->enum('performance_trend', ['improved', 'declined', 'maintained'])->default('maintained');
            $table->text('teacher_comment')->nullable();
            $table->text('headmaster_comment')->nullable();
            $table->text('recommendation')->nullable();
            $table->enum('promotion_status', ['promoted', 'probation', 'repeat', 'pending'])->default('pending');
            $table->decimal('fees_balance', 12, 2)->default(0);
            $table->enum('financial_clearance_status', ['cleared', 'pending'])->default('pending');
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->enum('status', ['draft', 'generated', 'approved', 'published'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'academic_year_id', 'term_id'], 'unique_student_term_report');
            $table->index(['academic_year_id', 'term_id', 'stream_id']);
            $table->index('status');
        });

        Schema::create('report_card_subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('report_card_id');
            $table->unsignedBigInteger('subject_id');
            $table->decimal('subject_average', 5, 2)->nullable();
            $table->string('subject_grade', 20)->nullable();
            $table->unsignedInteger('subject_points')->nullable();
            $table->decimal('class_average', 5, 2)->nullable();
            $table->unsignedInteger('subject_position')->nullable();
            $table->text('teacher_comment')->nullable();
            $table->timestamps();

            $table->unique(['report_card_id', 'subject_id'], 'unique_report_subject');
            $table->index('subject_id');
        });

        Schema::create('report_card_skill_assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('report_card_id');
            $table->string('skill_name', 120);
            $table->string('rating', 40);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('report_card_signatures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('report_card_id')->unique();
            $table->boolean('class_teacher_signed')->default(false);
            $table->boolean('headmaster_signed')->default(false);
            $table->timestamp('class_teacher_signed_at')->nullable();
            $table->timestamp('headmaster_signed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_card_signatures');
        Schema::dropIfExists('report_card_skill_assessments');
        Schema::dropIfExists('report_card_subjects');
        Schema::dropIfExists('report_cards');
    }
};
