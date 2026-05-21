<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('students', function (Blueprint $table) {
            $table->string('student_number', 30)->nullable()->unique()->after('admission_number');
            $table->string('national_id', 50)->nullable()->after('student_number');
        });
    }
    public function down(): void {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['student_number', 'national_id']);
        });
    }
};
