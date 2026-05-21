<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('teacher_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('stream_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedBigInteger('term_id');
            $table->timestamps();
            $table->unique(
                ['teacher_id', 'subject_id', 'stream_id', 'academic_year_id', 'term_id'],
                'unique_teacher_allocation'
            );
        });
    }
    public function down(): void { Schema::dropIfExists('teacher_allocations'); }
};
