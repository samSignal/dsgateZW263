<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_member_id')->unique();
            $table->string('teacher_code', 30)->unique();
            $table->string('specialization', 150)->nullable();
            $table->timestamps();

            $table->index('staff_member_id');
        });
    }
    public function down(): void { Schema::dropIfExists('teacher_profiles'); }
};
