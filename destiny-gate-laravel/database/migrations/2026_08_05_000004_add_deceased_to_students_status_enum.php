<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `students` MODIFY `status` ENUM('active','inactive','transferred','graduated','suspended','deceased') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `students` MODIFY `status` ENUM('active','inactive','transferred','graduated','suspended') NOT NULL DEFAULT 'active'");
    }
};
