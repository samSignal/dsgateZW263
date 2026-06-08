<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('student_address', 255)->nullable()->after('id_number');

            $table->string('previous_school', 150)->nullable()->after('student_address');
            $table->string('former_grade', 50)->nullable()->after('previous_school');
            $table->text('reason_for_joining')->nullable()->after('former_grade');

            $table->string('doc_student_id_path', 255)->nullable()->after('reason_for_joining');
            $table->string('doc_results_path', 255)->nullable()->after('doc_student_id_path');
            $table->string('doc_parent_id_path', 255)->nullable()->after('doc_results_path');
            $table->string('doc_transfer_letter_path', 255)->nullable()->after('doc_parent_id_path');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'student_address',
                'previous_school',
                'former_grade',
                'reason_for_joining',
                'doc_student_id_path',
                'doc_results_path',
                'doc_parent_id_path',
                'doc_transfer_letter_path',
            ]);
        });
    }
};

