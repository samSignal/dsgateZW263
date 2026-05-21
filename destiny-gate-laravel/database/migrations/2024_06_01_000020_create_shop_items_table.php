<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('shop_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_category_id');
            $table->string('item_name', 150);
            $table->string('item_code', 50)->unique();
            $table->text('description')->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->integer('quantity_in_stock')->default(0);
            $table->integer('reorder_level')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('shop_category_id');
            $table->index('is_active');
        });
    }
    public function down(): void { Schema::dropIfExists('shop_items'); }
};
