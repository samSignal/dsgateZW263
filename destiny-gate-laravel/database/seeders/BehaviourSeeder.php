<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BehaviourSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $yearName = date('Y');
        DB::table('academic_years')->insertOrIgnore([
            ['name' => $yearName, 'start_date' => date('Y') . '-01-01', 'end_date' => date('Y') . '-12-31', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
        $yearId = DB::table('academic_years')->where('name', $yearName)->value('id');
        DB::table('terms')->insertOrIgnore([
            ['academic_year_id' => $yearId, 'name' => 'Term 1', 'start_date' => date('Y') . '-01-01', 'end_date' => date('Y') . '-04-30', 'is_current' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['academic_year_id' => $yearId, 'name' => 'Term 2', 'start_date' => date('Y') . '-05-01', 'end_date' => date('Y') . '-08-31', 'is_current' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['academic_year_id' => $yearId, 'name' => 'Term 3', 'start_date' => date('Y') . '-09-01', 'end_date' => date('Y') . '-12-31', 'is_current' => 0, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $classes = DB::table('classes')->select('class_name', 'stream', 'capacity')->get();
        foreach ($classes as $class) {
            $formId = DB::table('forms')->where('name', $class->class_name)->value('id');
            if ($formId && $class->stream) {
                DB::table('streams')->insertOrIgnore([
                    'form_id' => $formId,
                    'name' => $class->stream,
                    'capacity' => $class->capacity ?? 40,
                    'class_teacher_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        DB::table('behaviour_categories')->insertOrIgnore([
            ['name' => 'Late Coming', 'type' => 'negative', 'description' => 'Arriving after the expected reporting time.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Absenteeism', 'type' => 'negative', 'description' => 'Unexplained absence from school or lessons.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Homework Not Done', 'type' => 'negative', 'description' => 'Repeated failure to complete assigned homework.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Fighting', 'type' => 'negative', 'description' => 'Physical confrontation or aggression.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bullying', 'type' => 'negative', 'description' => 'Bullying, intimidation, or harassment.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Disrespect', 'type' => 'negative', 'description' => 'Disrespect toward staff, students, or school rules.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Uniform Violation', 'type' => 'negative', 'description' => 'Failure to follow uniform expectations.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Absconding Lessons', 'type' => 'negative', 'description' => 'Skipping lessons while on campus.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Vandalism', 'type' => 'negative', 'description' => 'Damage to school property.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Good Conduct', 'type' => 'positive', 'description' => 'Positive behaviour, helpfulness, or exemplary conduct.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
