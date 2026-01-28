<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_transaction_requires_authentication(): void
    {
        $response = $this->getJson('/api/transaction');

        $response->assertStatus(401);
    }

    public function test_store_transaction_requires_authentication(): void
    {
        $response = $this->postJson('/api/transaction', [
            'wallet_id' => 1,
            'amount' => 10000,
            'description' => 'Test Transaction'
        ]);

        $response->assertStatus(401);
    }

    public function test_update_transaction_requires_authentication(): void
    {
        $response = $this->putJson('/api/transaction/1', [
            'amount' => 15000,
            'description' => 'Updated Transaction'
        ]);

        $response->assertStatus(401);
    }

    public function test_delete_transaction_requires_authentication(): void
    {
        $response = $this->deleteJson('/api/transaction/1');

        $response->assertStatus(401);
    }
}
