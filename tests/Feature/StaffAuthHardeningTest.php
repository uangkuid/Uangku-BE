<?php

namespace Tests\Feature;

use App\Models\StaffAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Epic 3 lanjutan: password reset panel, staff profile page, 2FA (opsional).
 */
class StaffAuthHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_password_reset_request_page(): void
    {
        $this->get('/staffsus/password-reset/request')->assertOk();
    }

    public function test_authenticated_staff_can_view_profile_page(): void
    {
        $staff = StaffAccount::create([
            'name' => 'Staff Test',
            'email' => 'staff-'.uniqid().'@uangku.test',
            'password' => bcrypt('password'),
        ]);
        $staff->assignRole(Role::findOrCreate('test-role-'.uniqid(), 'web'));

        $this->actingAs($staff, 'web');

        $this->get('/staffsus/profile')->assertOk();
    }

    public function test_staff_can_enable_and_disable_email_authentication(): void
    {
        $staff = StaffAccount::create([
            'name' => 'Staff Test',
            'email' => 'staff-'.uniqid().'@uangku.test',
            'password' => bcrypt('password'),
        ]);

        $this->assertFalse($staff->hasEmailAuthentication());

        $staff->toggleEmailAuthentication(true);
        $this->assertTrue($staff->fresh()->hasEmailAuthentication());

        $staff->toggleEmailAuthentication(false);
        $this->assertFalse($staff->fresh()->hasEmailAuthentication());
    }

    public function test_staff_app_authentication_secret_persists_through_default_select(): void
    {
        $staff = StaffAccount::create([
            'name' => 'Staff Test',
            'email' => 'staff-'.uniqid().'@uangku.test',
            'password' => bcrypt('password'),
        ]);

        $staff->saveAppAuthenticationSecret('SECRET123');

        $this->assertSame('SECRET123', $staff->fresh()->getAppAuthenticationSecret());
    }
}
