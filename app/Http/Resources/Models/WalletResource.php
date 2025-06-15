<?php

namespace App\Http\Resources\Models;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->wallets,
            'name' => $this->wallet->name,
            'amount' => $this->wallet->amount,
            'type' => $this->wallet->type,
            'status' => $this->wallet->status,
            'created_at' => optional($this->wallet->created_at)->timezone('Asia/Jakarta')->toIso8601String(),
            'updated_at' => optional($this->wallet->updated_at)->timezone('Asia/Jakarta')->toIso8601String(),
        ];
    }
}
