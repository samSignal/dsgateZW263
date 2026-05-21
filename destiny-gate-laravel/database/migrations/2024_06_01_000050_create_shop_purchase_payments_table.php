<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('student_purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_purchase_id');
            $table->unsignedBigInteger('finance_payment_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['cash', 'ecocash', 'bank_transfer', 'swipe', 'online', 'other'])->default('cash');
            $table->string('reference_number', 100)->nullable();
            $table->unsignedBigInteger('received_by');
            $table->date('payment_date');
            $table->timestamps();

            $table->index('student_purchase_id');
            $table->index('payment_date');
        });
    }
    public function down(): void { Schema::dropIfExists('student_purchase_payments'); }
};
