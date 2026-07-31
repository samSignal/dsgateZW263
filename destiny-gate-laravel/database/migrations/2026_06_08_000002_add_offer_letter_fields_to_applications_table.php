<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('offer_letter_token', 80)->nullable()->unique()->after('status');
            $table->unsignedSmallInteger('offer_letter_version')->default(1)->after('offer_letter_token');
            $table->timestamp('offer_letter_expires_at')->nullable()->after('offer_letter_version');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropUnique(['offer_letter_token']);
            $table->dropColumn(['offer_letter_token', 'offer_letter_version', 'offer_letter_expires_at']);
        });
    }
};

