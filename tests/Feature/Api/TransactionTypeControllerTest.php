<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionTypeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_transaction_types_returns_successful_response(): void
    {
        $response = $this->getJson('/api/transaction-type');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data'
            ]);
    }
}
