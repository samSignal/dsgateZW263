<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fee & Uniform Content Specification §5 requires "Order status: Ordered / Awaiting
 * Payment / Ready for Collection / Collected" as a dimension distinct from payment
 * status — this table only ever tracked payment status (unpaid/partial/paid/cancelled),
 * so there was no way to record a uniform as physically handed over. Deliberately not a
 * stored "collection_status" enum: whether an order is "Ready for Collection" vs
 * "Awaiting Payment" is fully derivable from payment_status + collected_at, and a
 * denormalized status column would just be another thing that can drift out of sync.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_purchases', function (Blueprint $table) {
            $table->timestamp('collected_at')->nullable()->after('payment_status');
            $table->foreignId('collected_by')->nullable()->after('collected_at')
                ->constrained('users')->nullOnDelete();
            $table->text('collection_notes')->nullable()->after('collected_by');
        });
    }

    public function down(): void
    {
        Schema::table('student_purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('collected_by');
            $table->dropColumn(['collected_at', 'collection_notes']);
        });
    }
};
