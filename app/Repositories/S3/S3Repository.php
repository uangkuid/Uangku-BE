<?php

namespace App\Repositories\S3;

use Illuminate\Http\UploadedFile;
use LaravelEasyRepository\Repository;

interface S3Repository extends Repository
{
    /**
     * Store the given file in S3 storage.
     */
    public function storeData(
        UploadedFile $data,
        string $fileName,
        string $path
    ): string;

    /**
     * Get the file from S3 storage.
     */
    public function getData(
        string $path,
        string $fileName
    ): string;
}
