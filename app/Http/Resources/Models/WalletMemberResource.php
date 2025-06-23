<?php

namespace App\Http\Resources\Models;

use App\Repositories\S3\S3Repository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $avatar = null;

        if (!empty($this->user->avatar)) {
            $avatar = app(S3Repository::class)->getData("avatar/{$this->user->id}", $this->user->avatar);
        }

        return [
            'id' => $this->user->id,
            'email' => $this->user->email,
            'avatar' => $avatar,
            'role' => $this->role,
            'created_at' => optional($this->created_at)->timezone('Asia/Jakarta')->toIso8601String(),
            'updated_at' => optional($this->updated_at)->timezone('Asia/Jakarta')->toIso8601String(),
        ];
    }
}
