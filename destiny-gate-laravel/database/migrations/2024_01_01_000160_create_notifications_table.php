<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['fee_reminder', 'fee_overdue', 'attendance_alert', 'behaviour_warning', 'academic_progress', 'announcement']);
            $table->string('title', 255);
            $table->text('message');
            $table->unsignedBigInteger('related_student_id')->nullable();
            $table->unsignedBigInteger('related_record_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('email_sent')->default(false);
            $table->timestamps();

            $table->index('user_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_notifications');
    }
};
