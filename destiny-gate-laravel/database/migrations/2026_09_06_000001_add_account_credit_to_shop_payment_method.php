<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::statement("ALTER TABLE student_purchase_payments MODIFY payment_method ENUM('cash','ecocash','bank_transfer','swipe','online','other','account_credit') DEFAULT 'cash'");
    }
    public function down(): void {
        DB::statement("ALTER TABLE student_purchase_payments MODIFY payment_method ENUM('cash','ecocash','bank_transfer','swipe','online','other') DEFAULT 'cash'");
    }
};
