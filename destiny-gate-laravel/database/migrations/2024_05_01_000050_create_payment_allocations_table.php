<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id');
            $table->unsignedBigInteger('student_bill_id');
            $table->decimal('amount_allocated', 12, 2);
            $table->timestamps();

            $table->index('payment_id');
            $table->index('student_bill_id');
        });
    }
    public function down(): void { Schema::dropIfExists('payment_allocations'); }
};
