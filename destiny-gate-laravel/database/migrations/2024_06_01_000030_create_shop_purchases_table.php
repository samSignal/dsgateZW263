<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // The purchase header table is created earlier as student_purchases.
        // This migration is kept as a no-op so existing migration ordering remains stable.
    }
    public function down(): void {}
};
