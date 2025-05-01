<?php

namespace App\Services\Family;

use App\Exceptions\EncryptionException;
use App\Exceptions\FamilyException;
use LaravelEasyRepository\BaseService;
use Random\RandomException;

interface FamilyService extends BaseService{

    /**
     * Create a new Family
     * @param string $token
     * @param string $name
     * @return array
     * @throws RandomException
     * @throws EncryptionException
     * @throws FamilyException
     */
    function createFamily(string $token, string $name): array;
}
