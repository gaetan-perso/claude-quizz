<?php declare(strict_types=1);

use App\Models\Category;
use App\Models\User;

describe('GET /api/v1/categories', function () {
    it('returns only active categories', function () {
        Category::factory()->create(['name' => 'Histoire', 'is_active' => true]);
        Category::factory()->create(['name' => 'Inactif', 'is_active' => false]);

        $user     = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Histoire');
    });

    it('returns categories sorted by name', function () {
        Category::factory()->create(['name' => 'Zoo', 'is_active' => true]);
        Category::factory()->create(['name' => 'Art', 'is_active' => true]);

        $user     = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Art')
            ->assertJsonPath('data.1.name', 'Zoo');
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/categories')->assertStatus(401);
    });

    it('returns correct fields', function () {
        Category::factory()->create(['is_active' => true]);

        $user     = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/categories');

        $response->assertJsonStructure(['data' => [['id', 'name', 'slug', 'icon', 'color']]]);
    });
});
