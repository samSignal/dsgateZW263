<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('categories')->upsert([
            [
                'name' => 'Sciences',
                'code' => 'SCI',
                'description' => 'Science-focused academic pathway.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Arts',
                'code' => 'ART',
                'description' => 'Arts and humanities academic pathway.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Commercials',
                'code' => 'COM',
                'description' => 'Commercial and business academic pathway.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'General',
                'code' => 'GEN',
                'description' => 'General academic pathway.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['code'], ['name', 'description', 'is_active', 'updated_at']);
    }
}
