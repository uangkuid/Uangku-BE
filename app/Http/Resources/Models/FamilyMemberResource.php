<?php

namespace App\Http\Resources\Models;

use App\Repositories\S3\S3Repository;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $avatar = null;

        if (!empty($this->users->avatar)) {
            $avatar = app(S3Repository::class)->getData("avatar/{$this->users->id}", $this->users->avatar);
        }

        return [
            'id' => $this->users->id,
            'email' => $this->users->email,
            'avatar' => $avatar,
            'role' => $this->role,
            'created_at' => optional($this->created_at)->timezone('Asia/Jakarta')->toIso8601String(),
            'updated_at' => optional($this->updated_at)->timezone('Asia/Jakarta')->toIso8601String(),
        ];
    }
}
