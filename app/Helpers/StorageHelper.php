<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StorageHelper
{
    /**
     * Safely generate a temporary URL from the given disk, returning null instead of
     * throwing when the disk is unreachable or misconfigured (e.g. MinIO/S3 down or
     * bucket not set).
     */
    public static function temporaryUrl(string $disk, string $path, \DateTimeInterface $expiration): ?string
    {
        try {
            return Storage::disk($disk)->temporaryUrl($path, $expiration);
        } catch (\Throwable $e) {
            Log::warning("Failed to generate temporary URL from disk [{$disk}]: {$e->getMessage()}", [
                'disk' => $disk,
                'path' => $path,
            ]);

            return null;
        }
    }
}
