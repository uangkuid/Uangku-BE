<?php

namespace App\Http\Resources\Models;

use App\Repositories\S3\S3Repository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $subCategory = null;

        if ($this->sub_categories) {
            $subCategory = [
                "id" => $this->sub_categories,
                "name" => $this->sub_category_name,
            ];
        }

        $icon = null;

        if (!empty($this->category_icon)) {
            $icon = app(S3Repository::class)->getData("category", $this->category_icon);
        }

        return [
            "id" => $this->id,
            "users" => $this->users,
            "categories" => [
                "id" => $this->categories,
                "name" => $this->category_name,
                "icon" => $icon,
            ],
            "transaction_type" => [
                "id" => $this->transaction_type_id,
                "name" => $this->transaction_type_name
            ],
            "amount" => $this->amount,
            "note" => $this->note,
            "sub_categories" => $subCategory,
            "wallet" => [
                "id" => $this->wallet_id    ,
                "name" => $this->wallet_name,
            ],
            'created_at' => optional($this->created_at)->timezone('Asia/Jakarta')->toIso8601String(),
            'updated_at' => optional($this->updated_at)->timezone('Asia/Jakarta')->toIso8601String(),
        ];
    }
}
