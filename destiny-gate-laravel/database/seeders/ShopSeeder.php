<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShopSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('shop_categories')->insertOrIgnore([
            ['name' => 'Uniforms', 'description' => 'School uniform items', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Books', 'description' => 'Textbooks and reading materials', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Stationery', 'description' => 'Exercise books, pens, and classroom supplies', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sportswear', 'description' => 'Tracksuits and sports clothing', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Trips', 'description' => 'School trips and excursions', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Examination Materials', 'description' => 'Exam stationery and materials', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Other', 'description' => 'Other school shop items', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $cats = DB::table('shop_categories')->pluck('id', 'name');

        DB::table('shop_items')->insertOrIgnore([
            ['shop_category_id' => $cats['Uniforms'],              'item_name' => 'School Shirt (Boys)',      'item_code' => 'UNI-001', 'unit_price' => 12.50, 'quantity_in_stock' => 50,  'reorder_level' => 10, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['shop_category_id' => $cats['Uniforms'],              'item_name' => 'School Shirt (Girls)',     'item_code' => 'UNI-002', 'unit_price' => 12.50, 'quantity_in_stock' => 50,  'reorder_level' => 10, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['shop_category_id' => $cats['Uniforms'],              'item_name' => 'School Trousers',          'item_code' => 'UNI-003', 'unit_price' => 15.00, 'quantity_in_stock' => 40,  'reorder_level' => 8,  'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['shop_category_id' => $cats['Books'],                 'item_name' => 'Mathematics Textbook',    'item_code' => 'BK-001',  'unit_price' => 8.00,  'quantity_in_stock' => 30,  'reorder_level' => 5,  'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['shop_category_id' => $cats['Books'],                 'item_name' => 'English Textbook',        'item_code' => 'BK-002',  'unit_price' => 8.00,  'quantity_in_stock' => 30,  'reorder_level' => 5,  'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['shop_category_id' => $cats['Stationery'],            'item_name' => 'Exercise Book (48 pages)','item_code' => 'ST-001',  'unit_price' => 0.50,  'quantity_in_stock' => 200, 'reorder_level' => 50, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['shop_category_id' => $cats['Stationery'],            'item_name' => 'Pen (Blue)',               'item_code' => 'ST-002',  'unit_price' => 0.30,  'quantity_in_stock' => 3,   'reorder_level' => 20, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['shop_category_id' => $cats['Sportswear'],            'item_name' => 'Tracksuit',               'item_code' => 'SP-001',  'unit_price' => 25.00, 'quantity_in_stock' => 20,  'reorder_level' => 5,  'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['shop_category_id' => $cats['Examination Materials'], 'item_name' => 'Answer Booklet',          'item_code' => 'EX-001',  'unit_price' => 0.20,  'quantity_in_stock' => 500, 'reorder_level' => 100,'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->command->info('Shop items seeded: ' . DB::table('shop_items')->count());
    }
}
