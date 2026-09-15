<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_service_markers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('service_code');
            $table->string('status')->default('inactive');
            $table->string('external_ref')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'service_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_service_markers');
    }
};
