<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_document_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->string('document_key', 60);
            $table->string('status', 20)->default('pending');
            $table->text('instructions')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->string('old_path', 255)->nullable();
            $table->string('new_path', 255)->nullable();
            $table->timestamps();

            $table->index(['application_id', 'status']);
            $table->index(['document_key', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_document_requests');
    }
};

