<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql' || !Schema::hasTable('student_purchases')) {
            return;
        }

        if (Schema::hasColumn('student_purchases', 'item_type')) {
            DB::statement('ALTER TABLE student_purchases MODIFY item_type VARCHAR(100) NULL');
        }
        if (Schema::hasColumn('student_purchases', 'description')) {
            DB::statement('ALTER TABLE student_purchases MODIFY description VARCHAR(255) NULL');
        }
        if (Schema::hasColumn('student_purchases', 'quantity')) {
            DB::statement('ALTER TABLE student_purchases MODIFY quantity INT NULL');
        }
        if (Schema::hasColumn('student_purchases', 'unit_price')) {
            DB::statement('ALTER TABLE student_purchases MODIFY unit_price DECIMAL(10,2) NULL');
        }
        if (Schema::hasColumn('student_purchases', 'total_price')) {
            DB::statement('ALTER TABLE student_purchases MODIFY total_price DECIMAL(10,2) NULL');
        }
    }

    public function down(): void
    {
        // Intentionally left as a no-op. Re-tightening legacy columns can break upgraded purchase records.
    }
};
