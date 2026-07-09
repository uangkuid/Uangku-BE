<?php

namespace App\Repositories\S3;

use App\Enums\RedisKey;
use App\Helpers\StorageHelper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use LaravelEasyRepository\Implementations\Eloquent;

class S3RepositoryImplement extends Eloquent implements S3Repository
{
    // Write something awesome :)
    /**
     * Store the given file in S3 storage.
     */
    public function storeData(UploadedFile $data, string $fileName, string $path): string
    {
        $filePath = $path.'/'.$fileName;

        // Store the file in S3 storage
        Storage::disk('minio')->put($filePath, file_get_contents($data));

        $tempUrl = Storage::disk('minio')->temporaryUrl($filePath, now()->addSeconds(86400));

        // Store the temporary URL in Redis cache
        $redisKey = RedisKey::S3->value.':'.$fileName;
        Redis::command('set', [$redisKey, $tempUrl, 'EX', (86400)]);

        // Return the file URL
        return $tempUrl;
    }

    /**
     * Get the file from S3 storage.
     */
    public function getData(string $path, string $fileName): string
    {
        $filePath = $path.'/'.$fileName;

        try {
            // Check if the file exists in S3 storage
            if (! Storage::disk('minio')->exists($filePath)) {
                // If the file does not exist, return an empty string or handle the error as needed
                return '';
            }
        } catch (\Throwable $e) {
            Log::warning("Failed to check existence on disk [minio]: {$e->getMessage()}", [
                'path' => $filePath,
            ]);

            return '';
        }

        $redisKey = RedisKey::S3->value.':'.$fileName;
        $cacheRedis = Redis::get($redisKey);

        if ($cacheRedis != null) {
            return $cacheRedis;
        }

        $url = StorageHelper::temporaryUrl('minio', $filePath, now()->addSeconds(86400));

        if ($url === null) {
            return '';
        }

        Redis::command('set', [$redisKey, $url, 'EX', (86400)]);

        // Return the file URL
        return $url;
    }
}
