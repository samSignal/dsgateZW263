<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('fee_categories', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->unique()->after('id');
            $table->enum('frequency', ['per_term', 'once_off', 'as_applicable', 'per_event', 'per_project'])
                ->nullable()->after('description');
        });
    }
    public function down(): void {
        Schema::table('fee_categories', function (Blueprint $table) {
            $table->dropColumn(['code', 'frequency']);
        });
    }
};
