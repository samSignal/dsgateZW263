<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('timetable_periods')) {
            Schema::create('timetable_periods', function (Blueprint $table) {
                $table->id();
                $table->string('name', 80);
                $table->unsignedInteger('period_number');
                $table->time('start_time');
                $table->time('end_time');
                $table->boolean('is_break')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique('period_number');
            });
        }

        if (!Schema::hasTable('timetable_rooms')) {
            Schema::create('timetable_rooms', function (Blueprint $table) {
                $table->id();
                $table->string('room_name', 120);
                $table->string('room_code', 30)->nullable();
                $table->enum('room_type', ['classroom', 'laboratory', 'computer_lab', 'hall', 'sports'])->default('classroom');
                $table->unsignedInteger('capacity')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('timetables')) {
            Schema::table('timetables', function (Blueprint $table) {
                if (!Schema::hasColumn('timetables', 'academic_year_id')) $table->unsignedBigInteger('academic_year_id')->nullable()->after('id');
                if (!Schema::hasColumn('timetables', 'term_id')) $table->unsignedBigInteger('term_id')->nullable()->after('academic_year_id');
                if (!Schema::hasColumn('timetables', 'form_id')) $table->unsignedBigInteger('form_id')->nullable()->after('term_id');
                if (!Schema::hasColumn('timetables', 'stream_id')) $table->unsignedBigInteger('stream_id')->nullable()->after('form_id');
                if (!Schema::hasColumn('timetables', 'room_id')) $table->unsignedBigInteger('room_id')->nullable()->after('teacher_id');
                if (!Schema::hasColumn('timetables', 'period_id')) $table->unsignedBigInteger('period_id')->nullable()->after('room_id');
                if (!Schema::hasColumn('timetables', 'timetable_type')) $table->enum('timetable_type', ['class', 'exam', 'remedial'])->default('class')->after('end_time');
                if (!Schema::hasColumn('timetables', 'remarks')) $table->text('remarks')->nullable()->after('timetable_type');
                if (!Schema::hasColumn('timetables', 'created_by')) $table->unsignedBigInteger('created_by')->nullable()->after('remarks');
            });

            try {
                DB::statement("ALTER TABLE timetables MODIFY day_of_week ENUM('monday','tuesday','wednesday','thursday','friday','saturday') NOT NULL");
            } catch (\Throwable $e) {
                // Keep existing enum on database engines that do not support this alteration.
            }
        }

        DB::table('timetable_periods')->insertOrIgnore([
            ['id' => 1, 'name' => 'Period 1', 'period_number' => 1, 'start_time' => '08:00:00', 'end_time' => '08:40:00', 'is_break' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Period 2', 'period_number' => 2, 'start_time' => '08:40:00', 'end_time' => '09:20:00', 'is_break' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Period 3', 'period_number' => 3, 'start_time' => '09:20:00', 'end_time' => '10:00:00', 'is_break' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Break', 'period_number' => 4, 'start_time' => '10:00:00', 'end_time' => '10:20:00', 'is_break' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Period 4', 'period_number' => 5, 'start_time' => '10:20:00', 'end_time' => '11:00:00', 'is_break' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Period 5', 'period_number' => 6, 'start_time' => '11:00:00', 'end_time' => '11:40:00', 'is_break' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_rooms');
        Schema::dropIfExists('timetable_periods');
    }
};
