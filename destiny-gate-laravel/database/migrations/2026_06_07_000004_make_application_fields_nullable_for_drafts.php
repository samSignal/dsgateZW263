<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `applications` MODIFY `first_name` varchar(100) NULL");
        DB::statement("ALTER TABLE `applications` MODIFY `last_name` varchar(100) NULL");
        DB::statement("ALTER TABLE `applications` MODIFY `email` varchar(320) NULL");
        DB::statement("ALTER TABLE `applications` MODIFY `phone` varchar(20) NULL");
        DB::statement("ALTER TABLE `applications` MODIFY `date_of_birth` date NULL");
        DB::statement("ALTER TABLE `applications` MODIFY `id_number` varchar(50) NULL");
        DB::statement("ALTER TABLE `applications` MODIFY `student_address` varchar(255) NULL");

        DB::statement("ALTER TABLE `applications` MODIFY `guardian_name` varchar(100) NULL");
        DB::statement("ALTER TABLE `applications` MODIFY `guardian_email` varchar(320) NULL");
        DB::statement("ALTER TABLE `applications` MODIFY `guardian_phone` varchar(20) NULL");

        DB::statement("ALTER TABLE `applications` MODIFY `intended_class` varchar(100) NULL");
        DB::statement("ALTER TABLE `applications` MODIFY `academic_year` varchar(20) NULL");

        DB::statement("ALTER TABLE `applications` MODIFY `previous_school` varchar(150) NULL");
        DB::statement("ALTER TABLE `applications` MODIFY `former_grade` varchar(50) NULL");
        DB::statement("ALTER TABLE `applications` MODIFY `reason_for_joining` text NULL");
    }

    public function down(): void
    {
    }
};

