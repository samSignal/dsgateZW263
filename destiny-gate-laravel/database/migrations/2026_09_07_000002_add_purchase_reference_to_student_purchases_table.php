<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('student_purchases', function (Blueprint $table) {
            $table->string('purchase_reference', 40)->nullable()->unique()->after('purchase_number');
            $table->timestamp('verified_at')->nullable()->after('status');
        });

        $purchases = DB::table('student_purchases')->whereNull('purchase_reference')->orderBy('id')->get();
        foreach ($purchases as $purchase) {
            DB::table('student_purchases')->where('id', $purchase->id)->update([
                'purchase_reference' => \App\Support\PaymentReferenceGenerator::generatePurchaseReference($purchase->created_at),
            ]);
        }
    }

    public function down(): void {
        Schema::table('student_purchases', function (Blueprint $table) {
            $table->dropColumn(['purchase_reference', 'verified_at']);
        });
    }
};
