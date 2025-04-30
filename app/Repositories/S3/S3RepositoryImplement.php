<?php

namespace App\Repositories\S3;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use LaravelEasyRepository\Implementations\Eloquent;

class S3RepositoryImplement extends Eloquent implements S3Repository
{

    // Write something awesome :)
    /**
     * Store the given file in S3 storage.
     * @param UploadedFile $data
     * @param string $fileName
     * @param string $path
     * @return string
     */
    function storeData(UploadedFile $data, string $fileName, string $path): string
    {
        $filePath = $path . '/' . $fileName;

        // Store the file in S3 storage
        Storage::disk('minio')->put($filePath, file_get_contents($data));

        // Return the file URL
        return Storage::disk('minio')->temporaryUrl($filePath, now()->addMinutes(30));
    }


    /**
     * Get the file from S3 storage.
     * @param string $path
     * @param string $fileName
     * @return string
     */
    function getData(string $path, string $fileName): string
    {
        $filePath = $path . '/' . $fileName;

        // Check if the file exists in S3 storage
        if (Storage::disk('minio')->exists($filePath)) {
            // Return the file URL
            return Storage::disk('minio')->temporaryUrl($filePath, now()->addMinutes(30));
        }

        // If the file does not exist, return an empty string or handle the error as needed
        return '';
    }
}
