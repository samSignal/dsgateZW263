<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('class_name', 100);
            $table->string('stream', 50)->nullable();
            $table->unsignedBigInteger('class_teacher_id')->nullable();
            $table->string('academic_year', 20);
            $table->integer('capacity')->nullable();
            $table->timestamps();

            $table->index('class_name');
            $table->index('academic_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
