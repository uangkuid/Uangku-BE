<?php

namespace App\Http\Resources\Models;

use App\Repositories\S3\S3Repository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $icon = null;

        if (!empty($this->icon)) {
            $icon = app(S3Repository::class)->getData("category", $this->icon);
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'icon' => $icon,
            'transaction_types' => [
                'id' => $this->transactionTypes->id ?? null,
                'name' => $this->transactionTypes->name ?? null,
            ],
            'created_at' => optional($this->created_at)->timezone('Asia/Jakarta')->toIso8601String(),
            'updated_at' => optional($this->updated_at)->timezone('Asia/Jakarta')->toIso8601String(),
        ];
    }
}
