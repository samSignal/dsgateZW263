<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->string('academic_year', 20);
            $table->enum('term', ['term1', 'term2', 'term3']);
            $table->enum('assessment_type', ['weekly_test', 'monthly_test', 'assignment', 'exam', 'project']);
            $table->decimal('marks', 5, 2)->nullable();
            $table->decimal('total_marks', 5, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->string('grade', 5)->nullable();
            $table->foreignId('recorded_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            $table->index('student_id');
            $table->index('class_id');
            $table->index('subject_id');
            $table->index('term');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_progress');
    }
};
