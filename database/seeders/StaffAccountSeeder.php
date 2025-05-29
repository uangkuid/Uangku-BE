<?php

namespace Database\Seeders;

use App\Services\Staff\StaffService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StaffAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $staffService = app(StaffService::class);

        DB::transaction(function () use ($staffService) {
            $staffService->register(
                name: "Administrator",
                email: "admin@uangku.com",
                password: "Password123",
                isSeeder: true
            );
        });
    }
}
