<?php

namespace App\Http\Resources\Models;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'access_id' => $this->access,
            'wallet_id' => $this->wallets,
            'amount' => $this->amount,
            'transaction_type' => $this->transaction_type,
            'created_at' => optional($this->created_at)->timezone('Asia/Jakarta')->toIso8601String(),
            'updated_at' => optional($this->updated_at)->timezone('Asia/Jakarta')->toIso8601String(),
        ];
    }
}
