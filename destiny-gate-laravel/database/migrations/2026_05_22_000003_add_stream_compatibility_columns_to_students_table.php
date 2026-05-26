<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'stream_id')) {
                $table->foreignId('stream_id')->nullable()->after('class_id')->constrained('streams')->onDelete('set null');
            }
            if (!Schema::hasColumn('students', 'form_id')) {
                $table->foreignId('form_id')->nullable()->after('stream_id')->constrained('forms')->onDelete('set null');
            }
            if (!Schema::hasColumn('students', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('form_id')->constrained('categories')->onDelete('set null');
            }
            if (!Schema::hasColumn('students', 'academic_year_id')) {
                $table->foreignId('academic_year_id')->nullable()->after('category_id')->constrained('academic_years')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'academic_year_id')) {
                $table->dropConstrainedForeignId('academic_year_id');
            }
            if (Schema::hasColumn('students', 'category_id')) {
                $table->dropConstrainedForeignId('category_id');
            }
            if (Schema::hasColumn('students', 'form_id')) {
                $table->dropConstrainedForeignId('form_id');
            }
            if (Schema::hasColumn('students', 'stream_id')) {
                $table->dropConstrainedForeignId('stream_id');
            }
        });
    }
};
