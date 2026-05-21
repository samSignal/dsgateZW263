<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('student_purchases')) {
            Schema::table('student_purchases', function (Blueprint $table) {
                if (!Schema::hasColumn('student_purchases', 'purchase_number')) $table->string('purchase_number', 30)->nullable()->unique()->after('id');
                if (!Schema::hasColumn('student_purchases', 'academic_year_id')) $table->unsignedBigInteger('academic_year_id')->nullable()->after('student_id');
                if (!Schema::hasColumn('student_purchases', 'term_id')) $table->unsignedBigInteger('term_id')->nullable()->after('academic_year_id');
                if (!Schema::hasColumn('student_purchases', 'purchased_by')) $table->unsignedBigInteger('purchased_by')->nullable()->after('term_id');
                if (!Schema::hasColumn('student_purchases', 'total_amount')) $table->decimal('total_amount', 12, 2)->default(0)->after('purchased_by');
                if (!Schema::hasColumn('student_purchases', 'amount_paid')) $table->decimal('amount_paid', 12, 2)->default(0)->after('total_amount');
                if (!Schema::hasColumn('student_purchases', 'balance')) $table->decimal('balance', 12, 2)->default(0)->after('amount_paid');
                if (!Schema::hasColumn('student_purchases', 'status')) $table->enum('status', ['unpaid', 'partial', 'paid', 'cancelled'])->default('unpaid')->after('balance');
                if (!Schema::hasColumn('student_purchases', 'payment_status')) $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid')->after('status');
                if (!Schema::hasColumn('student_purchases', 'notes')) $table->text('notes')->nullable()->after('payment_status');
            });
        }

        if (!Schema::hasTable('student_purchase_items')) {
            Schema::create('student_purchase_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_purchase_id');
                $table->unsignedBigInteger('shop_item_id');
                $table->integer('quantity');
                $table->decimal('unit_price', 12, 2);
                $table->decimal('line_total', 12, 2);
                $table->timestamps();
                $table->index('student_purchase_id');
                $table->index('shop_item_id');
            });
        }

        if (!Schema::hasTable('student_purchase_payments')) {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('student_purchase_payments');
        Schema::dropIfExists('student_purchase_items');
    }
};
