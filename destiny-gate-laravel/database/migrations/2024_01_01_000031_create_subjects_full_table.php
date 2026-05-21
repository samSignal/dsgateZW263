<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subject_group_id')->nullable();
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->unsignedTinyInteger('pass_mark')->default(50);
            $table->boolean('is_compulsory')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('subjects'); }
};
