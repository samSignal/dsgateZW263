<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('finance_fee_structures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('form_id')->nullable();
            $table->unsignedBigInteger('stream_id')->nullable();
            $table->unsignedBigInteger('fee_category_id');
            $table->string('name', 150);
            $table->decimal('amount', 12, 2);
            $table->date('due_date')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['academic_year_id', 'term_id', 'form_id', 'fee_category_id'], 'ffs_main_idx');
        });
    }
    public function down(): void { Schema::dropIfExists('finance_fee_structures'); }
};
