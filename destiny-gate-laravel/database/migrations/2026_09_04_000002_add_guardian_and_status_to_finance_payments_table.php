<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('finance_payments', function (Blueprint $table) {
            $table->foreignId('guardian_id')->nullable()->after('student_id')
                ->constrained('guardians')->nullOnDelete();
            $table->enum('status', ['active', 'reversed'])->default('active')->after('notes');
        });
    }
    public function down(): void {
        Schema::table('finance_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('guardian_id');
            $table->dropColumn('status');
        });
    }
};
