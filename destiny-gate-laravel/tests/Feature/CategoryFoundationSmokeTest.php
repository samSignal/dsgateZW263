<?php

namespace Tests\Feature;

use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CategoryFoundationSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_categories_are_seeded(): void
    {
        $this->seed(CategorySeeder::class);

        $this->assertDatabaseHas('categories', ['name' => 'Sciences', 'code' => 'SCI', 'is_active' => true]);
        $this->assertDatabaseHas('categories', ['name' => 'Arts', 'code' => 'ART', 'is_active' => true]);
        $this->assertDatabaseHas('categories', ['name' => 'Commercials', 'code' => 'COM', 'is_active' => true]);
        $this->assertDatabaseHas('categories', ['name' => 'General', 'code' => 'GEN', 'is_active' => true]);
    }

    public function test_category_can_be_created(): void
    {
        $this->withoutMiddleware();

        $this->postJson('/api/categories', [
            'name' => 'Technology',
            'code' => 'TECH',
            'description' => 'Technology pathway.',
        ])->assertCreated()
            ->assertJsonPath('name', 'Technology')
            ->assertJsonPath('code', 'TECH');

        $this->assertDatabaseHas('categories', [
            'name' => 'Technology',
            'code' => 'TECH',
            'is_active' => true,
        ]);
    }

    public function test_category_can_be_assigned_to_stream(): void
    {
        $this->withoutMiddleware();

        $formId = $this->createForm();
        $categoryId = $this->createCategory('Sciences', 'SCI');

        $this->postJson('/api/streams', [
            'form_id' => $formId,
            'category_id' => $categoryId,
            'name' => 'A',
            'capacity' => 35,
        ])->assertCreated()
            ->assertJsonPath('category_id', $categoryId)
            ->assertJsonPath('category_name', 'Sciences');

        $this->assertDatabaseHas('streams', [
            'form_id' => $formId,
            'category_id' => $categoryId,
            'name' => 'A',
        ]);
    }

    public function test_streams_can_be_filtered_by_category(): void
    {
        $this->withoutMiddleware();

        $formId = $this->createForm();
        $sciencesId = $this->createCategory('Sciences', 'SCI');
        $artsId = $this->createCategory('Arts', 'ART');

        DB::table('streams')->insert([
            ['form_id' => $formId, 'category_id' => $sciencesId, 'name' => 'Sciences A', 'capacity' => 40, 'created_at' => now(), 'updated_at' => now()],
            ['form_id' => $formId, 'category_id' => $artsId, 'name' => 'Arts A', 'capacity' => 40, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->getJson('/api/streams?category_id=' . $sciencesId)
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Sciences A')
            ->assertJsonPath('0.category_name', 'Sciences');
    }

    public function test_inactive_category_cannot_be_assigned_to_stream(): void
    {
        $this->withoutMiddleware();

        $formId = $this->createForm();
        $categoryId = $this->createCategory('Dormant Pathway', 'DORM', false);

        $this->postJson('/api/streams', [
            'form_id' => $formId,
            'category_id' => $categoryId,
            'name' => 'Dormant A',
            'capacity' => 35,
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Inactive categories cannot be assigned to streams.');

        $this->assertDatabaseMissing('streams', [
            'form_id' => $formId,
            'category_id' => $categoryId,
            'name' => 'Dormant A',
        ]);
    }

    private function createForm(): int
    {
        return DB::table('forms')->insertGetId([
            'name' => 'Form 5',
            'level' => 5,
            'description' => 'Advanced Level',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCategory(string $name, string $code, bool $active = true): int
    {
        return DB::table('categories')->insertGetId([
            'name' => $name,
            'code' => $code,
            'description' => $name . ' pathway.',
            'is_active' => $active,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
