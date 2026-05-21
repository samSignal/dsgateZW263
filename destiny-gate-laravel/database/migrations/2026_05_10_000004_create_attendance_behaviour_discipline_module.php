<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('guardians', 'can_receive_notifications')) {
            Schema::table('guardians', function (Blueprint $table) {
                $table->boolean('can_receive_notifications')->default(true)->after('is_primary_contact');
            });
        }

        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('form_id');
            $table->unsignedBigInteger('stream_id');
            $table->date('attendance_date');
            $table->enum('session_type', ['morning', 'afternoon', 'lesson']);
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('taken_by');
            $table->enum('status', ['draft', 'submitted'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['stream_id', 'attendance_date', 'session_type', 'subject_id'], 'attendance_session_unique');
            $table->index(['academic_year_id', 'term_id']);
            $table->index('attendance_date');
        });

        Schema::create('student_attendance_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_session_id');
            $table->unsignedBigInteger('student_id');
            $table->enum('status', ['present', 'absent', 'late', 'excused', 'sick', 'early_departure'])->default('present');
            $table->time('arrival_time')->nullable();
            $table->text('reason')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('recorded_by');
            $table->timestamps();

            $table->unique(['attendance_session_id', 'student_id'], 'attendance_record_unique');
            $table->index('student_id');
            $table->index('status');
        });

        Schema::create('behaviour_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->enum('type', ['positive', 'negative'])->default('negative');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('behaviour_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('incident_number', 30)->unique();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('behaviour_category_id');
            $table->date('incident_date');
            $table->string('title', 150);
            $table->text('description');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->unsignedBigInteger('reported_by');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->enum('review_status', ['pending', 'reviewed', 'escalated', 'closed'])->default('pending');
            $table->boolean('parent_notified')->default(false);
            $table->timestamps();

            $table->index('student_id');
            $table->index(['academic_year_id', 'term_id']);
            $table->index('severity');
            $table->index('review_status');
        });

        Schema::create('discipline_actions', function (Blueprint $table) {
            $table->id();
            $table->string('action_number', 30)->unique();
            $table->unsignedBigInteger('behaviour_incident_id');
            $table->unsignedBigInteger('student_id');
            $table->enum('action_type', ['verbal_warning', 'written_warning', 'parent_meeting', 'detention', 'suspension', 'headmaster_review', 'counselling', 'other']);
            $table->date('action_date');
            $table->text('description');
            $table->unsignedBigInteger('issued_by');
            $table->boolean('parent_required')->default(false);
            $table->boolean('parent_notified')->default(false);
            $table->date('follow_up_date')->nullable();
            $table->enum('status', ['open', 'completed', 'cancelled'])->default('open');
            $table->timestamps();

            $table->index('behaviour_incident_id');
            $table->index('student_id');
            $table->index('status');
        });

        Schema::create('student_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('guardian_id')->nullable();
            $table->enum('notification_type', ['attendance', 'behaviour', 'discipline', 'general']);
            $table->string('title', 150);
            $table->text('message');
            $table->enum('channel', ['portal', 'sms', 'email', 'whatsapp'])->default('portal');
            $table->enum('status', ['pending', 'sent', 'failed', 'read'])->default('sent');
            $table->string('related_type', 100)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('student_id');
            $table->index('guardian_id');
            $table->index(['related_type', 'related_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_notifications');
        Schema::dropIfExists('discipline_actions');
        Schema::dropIfExists('behaviour_incidents');
        Schema::dropIfExists('behaviour_categories');
        Schema::dropIfExists('student_attendance_records');
        Schema::dropIfExists('attendance_sessions');
    }
};
