<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_number', 50)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 320);
            $table->string('phone', 20);
            $table->date('date_of_birth');
            $table->string('guardian_name', 100);
            $table->string('guardian_email', 320);
            $table->string('guardian_phone', 20);
            $table->string('intended_class', 100);
            $table->string('academic_year', 20);
            $table->enum('status', ['pending', 'approved', 'rejected', 'enrolled'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('enrolled_student_id')->nullable()->constrained('students')->onDelete('set null');
            $table->timestamps();

            $table->index('status');
            $table->index('academic_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
