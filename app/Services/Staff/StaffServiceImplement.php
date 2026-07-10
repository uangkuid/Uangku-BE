<?php

namespace App\Services\Staff;

use App\Exceptions\UserException;
use App\Repositories\StaffAccount\StaffAccountRepository;
use LaravelEasyRepository\Service;

class StaffServiceImplement extends Service implements StaffService
{

    protected StaffAccountRepository $staffAccountRepository;

    public function __construct(StaffAccountRepository $staffAccountRepository)
    {
        $this->staffAccountRepository = $staffAccountRepository;
    }

    /**
     * Register a new staff account.
     * @param string $name
     * @param string $email
     * @param string $password
     * @return array
     * @throws UserException
     */
    function register(
        string $name,
        string $email,
        string $password
    ): array
    {
        $isExist = $this->staffAccountRepository->isNameExist($name);

        if ($isExist) {
            throw new UserException("Staff account with name {$name} already exists.");
        }

        $staff = $this->staffAccountRepository->create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password),
        ]);

        return [
            'id' => $staff->id,
            'name' => $staff->name,
            'email' => $staff->email,
        ];
    }
}
