<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_sub_categories_requires_authentication(): void
    {
        $response = $this->getJson('/api/categories/1');

        $response->assertStatus(401);
    }

    public function test_store_sub_category_requires_authentication(): void
    {
        $response = $this->postJson('/api/categories/1', [
            'name' => 'Test SubCategory'
        ]);

        $response->assertStatus(401);
    }

    public function test_update_sub_category_requires_authentication(): void
    {
        $response = $this->putJson('/api/categories/1/1', [
            'name' => 'Updated SubCategory'
        ]);

        $response->assertStatus(401);
    }

    public function test_delete_sub_category_requires_authentication(): void
    {
        $response = $this->deleteJson('/api/categories/1/1');

        $response->assertStatus(401);
    }
}
