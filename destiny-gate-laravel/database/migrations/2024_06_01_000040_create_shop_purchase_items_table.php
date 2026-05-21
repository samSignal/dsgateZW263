<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
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
    public function down(): void { Schema::dropIfExists('student_purchase_items'); }
};
