<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('streams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('forms')->onDelete('restrict');
            $table->string('name', 100);
            $table->unsignedSmallInteger('capacity')->default(40);
            $table->unsignedBigInteger('class_teacher_id')->nullable();
            $table->timestamps();
            $table->unique(['form_id', 'name']);
        });
    }
    public function down(): void { Schema::dropIfExists('streams'); }
};
