<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `applications` MODIFY `status` ENUM('pending','offered','waiting_list','rejected','enrolled','approved') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `applications` MODIFY `status` ENUM('pending','approved','rejected','enrolled') NOT NULL DEFAULT 'pending'");
    }
};

