<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('payment_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_payment_id')->constrained('finance_payments')->cascadeOnDelete();
            $table->enum('type', ['reversal'])->default('reversal');
            $table->text('reason');
            $table->foreignId('adjusted_by')->constrained('users');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('payment_adjustments'); }
};
