<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('report_cards') && !Schema::hasColumn('report_cards', 'financial_clearance_status')) {
            Schema::table('report_cards', function (Blueprint $table) {
                $table->enum('financial_clearance_status', ['cleared', 'pending'])->default('pending')->after('fees_balance');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('report_cards') && Schema::hasColumn('report_cards', 'financial_clearance_status')) {
            Schema::table('report_cards', function (Blueprint $table) {
                $table->dropColumn('financial_clearance_status');
            });
        }
    }
};
