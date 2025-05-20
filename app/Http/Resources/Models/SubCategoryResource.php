<?php

namespace App\Http\Resources\Models;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubCategoryResource extends JsonResource
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
            'name' => $this->name,
            'users' => [
                'id' => $this->user?->id,
                'email' => $this->user?->email,
                'avatar' => $this->user?->avatar,
            ],
            'categories' => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
                'transaction_types' => [
                    'id' => $this->category?->transactionType?->id,
                    'name' => $this->category?->transactionType?->name,
                ],
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
