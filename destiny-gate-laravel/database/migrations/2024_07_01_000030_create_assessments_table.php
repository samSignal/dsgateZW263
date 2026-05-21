<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->string('assessment_number', 30)->unique();
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('stream_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('assessment_type_id');
            $table->string('title', 200);
            $table->decimal('total_marks', 8, 2)->default(100);
            $table->date('assessment_date');
            $table->unsignedBigInteger('created_by');
            $table->enum('status', ['draft', 'open', 'submitted', 'approved', 'cancelled'])->default('open');
            $table->timestamps();

            $table->index(['academic_year_id', 'term_id', 'stream_id', 'subject_id'], 'ass_main_idx');
            $table->index('status');
        });
    }
    public function down(): void { Schema::dropIfExists('assessments'); }
};
