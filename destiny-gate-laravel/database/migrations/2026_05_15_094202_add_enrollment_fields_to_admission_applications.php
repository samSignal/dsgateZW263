<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('admission_applications', 'acceptance_date')) {
                $table->timestamp('acceptance_date')->nullable()->after('reviewed_at');
            }
            if (!Schema::hasColumn('admission_applications', 'enrollment_completed_at')) {
                $table->timestamp('enrollment_completed_at')->nullable()->after('acceptance_date');
            }
            if (!Schema::hasColumn('admission_applications', 'enrolled_student_id')) {
                $table->unsignedBigInteger('enrolled_student_id')->nullable()->after('enrollment_completed_at');
            }
            if (!Schema::hasColumn('admission_applications', 'guardian_user_id')) {
                $table->unsignedBigInteger('guardian_user_id')->nullable()->after('enrolled_student_id');
            }
            if (!Schema::hasColumn('admission_applications', 'student_user_id')) {
                $table->unsignedBigInteger('student_user_id')->nullable()->after('guardian_user_id');
            }
            if (!Schema::hasColumn('admission_applications', 'assigned_stream_id')) {
                $table->unsignedBigInteger('assigned_stream_id')->nullable()->after('student_user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admission_applications', function (Blueprint $table) {
            foreach (['acceptance_date', 'enrollment_completed_at', 'enrolled_student_id', 'guardian_user_id', 'student_user_id', 'assigned_stream_id'] as $column) {
                if (Schema::hasColumn('admission_applications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
