<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('staff')->onDelete('cascade');
            $table->string('academic_year', 20);
            $table->enum('term', ['term1', 'term2', 'term3']);
            $table->text('progress')->nullable();
            $table->text('participation')->nullable();
            $table->text('homework')->nullable();
            $table->text('behaviour')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->text('strengths')->nullable();
            $table->timestamps();

            $table->index('student_id');
            $table->index('class_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_comments');
    }
};
