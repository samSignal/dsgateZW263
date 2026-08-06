<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['cash', 'ecocash', 'visa', 'mastercard', 'omari', 'innbucks', 'bank_transfer', 'other'])->default('cash');
            $table->string('reference_number', 100)->nullable();
            $table->foreignId('received_by')->constrained('users');
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('application_id');
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_deposits');
    }
};
