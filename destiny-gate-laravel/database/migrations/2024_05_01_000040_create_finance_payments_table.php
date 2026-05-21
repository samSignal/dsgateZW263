<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('finance_payments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 30)->unique();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedBigInteger('term_id');
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['cash', 'ecocash', 'bank_transfer', 'swipe', 'online', 'other'])->default('cash');
            $table->string('reference_number', 100)->nullable();
            $table->string('payer_name', 150)->nullable();
            $table->string('payer_phone', 20)->nullable();
            $table->unsignedBigInteger('received_by');
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('student_id');
            $table->index('payment_date');
            $table->index(['academic_year_id', 'term_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('finance_payments'); }
};
