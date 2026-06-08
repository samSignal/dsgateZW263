<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->unsignedBigInteger('academic_year_id')->nullable()->after('id');
            $table->unsignedBigInteger('term_id')->nullable()->after('academic_year_id');
            $table->unsignedBigInteger('form_id')->nullable()->after('term_id');
            $table->unsignedBigInteger('category_id')->nullable()->after('form_id');

            $table->boolean('is_draft')->default(false)->after('status');
            $table->unsignedTinyInteger('last_saved_step')->nullable()->after('is_draft');
            $table->string('resume_token', 80)->nullable()->unique()->after('last_saved_step');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropUnique(['resume_token']);
            $table->dropColumn([
                'academic_year_id',
                'term_id',
                'form_id',
                'category_id',
                'is_draft',
                'last_saved_step',
                'resume_token',
            ]);
        });
    }
};

