<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Support\Facades\Cache;

class CachedEloquentStaffProvider extends EloquentUserProvider
{
    public function retrieveById($identifier)
    {
        $cacheKey = "staff.{$identifier}";

        return Cache::remember($cacheKey, 1800, function () use ($identifier) {
            return parent::retrieveById($identifier);
        });
    }

    public function retrieveByToken($identifier, $token)
    {
        // Tidak perlu cache untuk remember_token lookup
        return parent::retrieveByToken($identifier, $token);
    }
}
