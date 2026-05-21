<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 20);
            $table->string('class_name', 100);
            $table->enum('term', ['term1', 'term2', 'term3']);
            $table->decimal('amount', 12, 2);
            $table->date('due_date');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('academic_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structures');
    }
};
