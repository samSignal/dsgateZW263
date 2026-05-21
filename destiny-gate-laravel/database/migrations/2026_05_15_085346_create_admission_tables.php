<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admission_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_number')->unique();
            $table->string('tracking_token')->unique();
            $table->enum('application_type', ['new_intake', 'transfer']);
            
            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->unsignedBigInteger('term_id')->nullable();

            // Student Details
            $table->string('student_first_name');
            $table->string('student_last_name');
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('birth_certificate_number');
            $table->string('student_national_id')->nullable();
            $table->string('passport_photo')->nullable();

            // Guardian Details
            $table->string('guardian_name')->nullable();
            $table->string('guardian_national_id')->nullable();
            $table->string('guardian_phone')->nullable();
            $table->string('emergency_phone')->nullable();
            $table->string('guardian_email')->nullable();
            $table->text('address')->nullable();
            $table->string('occupation')->nullable();

            // Academic Details
            $table->unsignedBigInteger('applying_form_id')->nullable();
            // New Intake
            $table->string('grade7_school')->nullable();
            $table->string('grade7_results')->nullable();
            // Transfer
            $table->string('previous_school_name')->nullable();
            $table->string('current_form')->nullable();
            $table->text('transfer_reason')->nullable();
            $table->string('last_term_average')->nullable();

            // Additional
            $table->text('medical_information')->nullable();
            $table->text('reason_for_joining')->nullable();

            // Status
            $table->enum('status', [
                'draft', 'submitted', 'under_review', 'interview_scheduled', 
                'documents_required', 'accepted', 'rejected', 'waitlisted', 
                'enrollment_pending', 'enrolled'
            ])->default('draft');
            
            $table->integer('current_step')->default(1);
            $table->boolean('is_submitted')->default(false);
            $table->integer('progress_percentage')->default(0);
            $table->integer('completion_percentage')->default(0);
            
            $table->timestamp('status_updated_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('draft_last_saved_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('remarks')->nullable();

            $table->timestamps();
        });

        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->string('document_type'); // birth_certificate, passport_photo, etc.
            $table->string('file_name');
            $table->string('file_path');
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();

            $table->foreign('application_id')->references('id')->on('admission_applications')->onDelete('cascade');
        });

        Schema::create('application_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->string('title');
            $table->text('message');
            $table->enum('notification_channel', ['system', 'email', 'whatsapp']);
            $table->enum('notification_type', ['info', 'success', 'warning', 'rejected']);
            $table->boolean('is_read')->default(false);
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('application_id')->references('id')->on('admission_applications')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_notifications');
        Schema::dropIfExists('application_documents');
        Schema::dropIfExists('admission_applications');
    }
};
