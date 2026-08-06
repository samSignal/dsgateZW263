<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('application_id')->nullable()->unique()->after('user_id')->constrained('applications')->onDelete('set null');
            $table->timestamp('document_verified_at')->nullable()->after('status');
            $table->foreignId('document_verified_by')->nullable()->after('document_verified_at')->constrained('users')->onDelete('set null');
            $table->date('verification_due_at')->nullable()->after('document_verified_by');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('application_id');
            $table->dropConstrainedForeignId('document_verified_by');
            $table->dropColumn(['document_verified_at', 'verification_due_at']);
        });
    }
};
