<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('finance_payments', function (Blueprint $table) {
            // Two distinct identifiers: payment_reference (system-generated, tamper-resistant,
            // what a receipt's QR code points at for public verification) vs receipt_number
            // (the existing official document number). reference_number stays as-is — that's
            // the EXTERNAL bank/EcoCash transaction code the payer supplies, a different thing.
            $table->string('payment_reference', 40)->nullable()->unique()->after('receipt_number');
            $table->timestamp('verified_at')->nullable()->after('status');
        });

        // Backfill so every existing payment is verifiable too, not just new ones.
        $payments = DB::table('finance_payments')->whereNull('payment_reference')->orderBy('id')->get();
        foreach ($payments as $payment) {
            DB::table('finance_payments')->where('id', $payment->id)->update([
                'payment_reference' => \App\Support\PaymentReferenceGenerator::generateReference($payment->created_at),
            ]);
        }
    }

    public function down(): void {
        Schema::table('finance_payments', function (Blueprint $table) {
            $table->dropColumn(['payment_reference', 'verified_at']);
        });
    }
};
