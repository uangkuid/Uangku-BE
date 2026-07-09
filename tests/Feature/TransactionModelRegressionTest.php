<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionType;
use App\Models\User;
use App\Repositories\Transaction\TransactionRepositoryImplement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regresi untuk bug: Transaction model punya 'wallets'/'families' di $fillable/$defaultSelect
 * padahal kolom itu tidak ada di tabel `transactions` — setiap query Transaction crash,
 * dan pembuatan transaksi dalam family wallet crash saat insert.
 */
class TransactionModelRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(): Category
    {
        $type = TransactionType::create(['name' => 'Expense']);

        return Category::create([
            'name' => 'Food',
            'transaction_types' => $type->id,
        ]);
    }

    public function test_transaction_query_does_not_crash(): void
    {
        $user = User::factory()->create();
        $category = $this->makeCategory();

        Transaction::create([
            'users' => $user->id,
            'categories' => $category->id,
            'transaction_type' => $category->transaction_types,
            'amount' => 'encrypted-amount',
            'note' => 'encrypted-note',
        ]);

        $this->assertSame(1, Transaction::query()->count());
        $this->assertNotNull(Transaction::query()->first());
    }

    public function test_create_transaction_with_family_does_not_crash(): void
    {
        $user = User::factory()->create();
        $category = $this->makeCategory();

        $repository = app(TransactionRepositoryImplement::class);

        $transaction = $repository->createTransaction(
            userId: $user->id,
            categoryId: $category->id,
            transactionTypeId: $category->transaction_types,
            amount: 'encrypted-amount',
            description: 'encrypted-note',
            family: (string) Str::uuid(),
        );

        $this->assertNotNull($transaction->id);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
    }
}
