<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('middle_name', 100)->nullable()->after('first_name');
            $table->string('id_number', 50)->nullable()->after('date_of_birth');

            $table->string('guardian2_name', 100)->nullable()->after('guardian_phone');
            $table->string('guardian2_email', 320)->nullable()->after('guardian2_name');
            $table->string('guardian2_phone', 20)->nullable()->after('guardian2_email');

            $table->string('guardian3_name', 100)->nullable()->after('guardian2_phone');
            $table->string('guardian3_email', 320)->nullable()->after('guardian3_name');
            $table->string('guardian3_phone', 20)->nullable()->after('guardian3_email');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'middle_name',
                'id_number',
                'guardian2_name',
                'guardian2_email',
                'guardian2_phone',
                'guardian3_name',
                'guardian3_email',
                'guardian3_phone',
            ]);
        });
    }
};

