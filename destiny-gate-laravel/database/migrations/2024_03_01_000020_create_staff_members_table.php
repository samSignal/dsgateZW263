<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('staff_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->unique();
            $table->string('staff_number', 50)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->enum('gender', ['male', 'female', 'other']);
            $table->date('date_of_birth')->nullable();
            $table->string('phone', 20);
            $table->string('email', 320)->unique();
            $table->text('address')->nullable();
            $table->string('national_id', 50)->nullable();
            $table->unsignedBigInteger('department_id');
            $table->string('job_title', 100);
            $table->enum('employment_type', ['full_time', 'part_time', 'contract'])->default('full_time');
            $table->date('employment_date');
            $table->string('profile_photo', 255)->nullable();
            $table->enum('status', ['active', 'on_leave', 'suspended', 'resigned'])->default('active');
            $table->timestamps();

            $table->index('department_id');
            $table->index('status');
            $table->index('staff_number');
        });
    }
    public function down(): void { Schema::dropIfExists('staff_members'); }
};
