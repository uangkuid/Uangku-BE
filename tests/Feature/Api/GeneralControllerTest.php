<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GeneralControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_feature_status_returns_successful_response(): void
    {
        $response = $this->getJson('/api/general/feature-status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data'
            ]);
    }

    public function test_get_system_config_returns_successful_response(): void
    {
        $response = $this->getJson('/api/general/system-config');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data'
            ]);
    }

    public function test_fallback_route_returns_404(): void
    {
        $response = $this->getJson('/api/non-existent-route');

        $response->assertStatus(404)
            ->assertJson([
                'code' => 404,
                'message' => 'Data not found'
            ]);
    }
}
