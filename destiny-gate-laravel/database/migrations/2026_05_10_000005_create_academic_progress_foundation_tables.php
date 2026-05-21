<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('subjects', 'is_active')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('is_compulsory');
            });
        }

        if (!Schema::hasTable('stream_subjects')) Schema::create('stream_subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('form_id');
            $table->unsignedBigInteger('stream_id');
            $table->unsignedBigInteger('subject_id');
            $table->boolean('is_compulsory')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['academic_year_id', 'term_id', 'stream_id', 'subject_id'], 'unique_stream_subject');
            $table->index(['form_id', 'stream_id']);
            $table->index('subject_id');
        });

        if (!Schema::hasTable('teacher_subject_allocations')) Schema::create('teacher_subject_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('stream_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['teacher_id', 'subject_id', 'stream_id', 'academic_year_id', 'term_id'], 'unique_teacher_subject_allocation');
            $table->index(['teacher_id', 'academic_year_id', 'term_id'], 'tsa_teacher_year_term_idx');
            $table->index(['stream_id', 'subject_id'], 'tsa_stream_subject_idx');
        });

        if (!Schema::hasTable('student_subjects')) Schema::create('student_subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('stream_subject_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedBigInteger('term_id');
            $table->boolean('is_compulsory')->default(false);
            $table->enum('enrollment_status', ['active', 'dropped'])->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'subject_id', 'academic_year_id', 'term_id'], 'unique_student_subject');
            $table->index(['student_id', 'enrollment_status']);
            $table->index('stream_subject_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_subjects');
        Schema::dropIfExists('teacher_subject_allocations');
        Schema::dropIfExists('stream_subjects');
    }
};
