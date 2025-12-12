<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WalletControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_wallet_requires_authentication(): void
    {
        $response = $this->getJson('/api/wallet');

        $response->assertStatus(401);
    }

    public function test_store_wallet_requires_authentication(): void
    {
        $response = $this->postJson('/api/wallet', [
            'name' => 'Test Wallet'
        ]);

        $response->assertStatus(401);
    }

    public function test_get_wallet_member_requires_authentication(): void
    {
        $response = $this->getJson('/api/wallet/1/member');

        $response->assertStatus(401);
    }

    public function test_get_wallet_snapshot_requires_authentication(): void
    {
        $response = $this->getJson('/api/wallet/1/snapshot');

        $response->assertStatus(401);
    }

    public function test_get_wallet_transaction_requires_authentication(): void
    {
        $response = $this->getJson('/api/wallet/1/transaction');

        $response->assertStatus(401);
    }

    public function test_update_wallet_requires_authentication(): void
    {
        $response = $this->putJson('/api/wallet/1', [
            'name' => 'Updated Wallet'
        ]);

        $response->assertStatus(401);
    }

    public function test_update_wallet_status_requires_authentication(): void
    {
        $response = $this->postJson('/api/wallet/1/status', [
            'status' => 'active'
        ]);

        $response->assertStatus(401);
    }

    public function test_add_wallet_member_requires_authentication(): void
    {
        $response = $this->postJson('/api/wallet/1/member', [
            'user_id' => 1
        ]);

        $response->assertStatus(401);
    }

    public function test_get_wallet_family_member_requires_authentication(): void
    {
        $response = $this->getJson('/api/wallet/1/family');

        $response->assertStatus(401);
    }

    public function test_revoke_wallet_member_requires_authentication(): void
    {
        $response = $this->postJson('/api/wallet/1/member/1/revoke');

        $response->assertStatus(401);
    }
}
