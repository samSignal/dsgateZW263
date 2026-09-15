<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('academic_year');
            $table->timestamps();

            $table->unique(['staff_id', 'class_id', 'subject_id', 'academic_year'], 'teacher_subjects_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_subjects');
    }
};
