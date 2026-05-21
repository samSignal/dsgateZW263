<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']);
            $table->integer('period_number');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('staff')->onDelete('cascade');
            $table->string('start_time', 10);
            $table->string('end_time', 10);
            $table->string('room', 50)->nullable();
            $table->string('academic_year', 20);
            $table->timestamps();

            $table->index('class_id');
            $table->index('day_of_week');
            $table->index('period_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetables');
    }
};
