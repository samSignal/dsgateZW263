<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssessmentSeeder extends Seeder
{
    public function run(): void
    {
        // Assessment Types
        DB::table('assessment_types')->insertOrIgnore([
            ['name' => 'Weekly Test',       'weight_percentage' => 5,  'description' => 'Weekly class test',              'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Monthly Test',      'weight_percentage' => 10, 'description' => 'Monthly assessment',             'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Assignment',        'weight_percentage' => 10, 'description' => 'Homework or project assignment', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Mid-Term Exam',     'weight_percentage' => 30, 'description' => 'Mid-term examination',           'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'End of Term Exam',  'weight_percentage' => 45, 'description' => 'End of term examination',        'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Grading Scale (Zimbabwe O-Level style)
        DB::table('grading_scales')->insertOrIgnore([
            ['name' => 'Standard', 'min_percentage' => 80, 'max_percentage' => 100, 'grade' => 'A',  'remark' => 'Excellent',          'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Standard', 'min_percentage' => 70, 'max_percentage' => 79,  'grade' => 'B',  'remark' => 'Very Good',          'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Standard', 'min_percentage' => 60, 'max_percentage' => 69,  'grade' => 'C',  'remark' => 'Good',               'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Standard', 'min_percentage' => 50, 'max_percentage' => 59,  'grade' => 'D',  'remark' => 'Satisfactory',       'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Standard', 'min_percentage' => 40, 'max_percentage' => 49,  'grade' => 'E',  'remark' => 'Pass',               'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Standard', 'min_percentage' => 0,  'max_percentage' => 39,  'grade' => 'U',  'remark' => 'Ungraded / Fail',    'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->command->info('Assessment types: ' . DB::table('assessment_types')->count());
        $this->command->info('Grading scales: '   . DB::table('grading_scales')->count());
    }
}
