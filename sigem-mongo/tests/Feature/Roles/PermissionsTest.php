<?php

namespace Tests\Feature\Roles;

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicioSocialUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AdminUserSeeder::class);
        $this->seed(ServicioSocialUserSeeder::class);
    }

    public function test_admin_has_permission_access_admin_panel(): void
    {
        $admin = User::where('email', 'admin@tecnm.edu.mx')->first();
        $this->assertTrue($admin->hasPermissionTo(RoleEnum::PERM_ACCESS_ADMIN));
    }

    public function test_servicio_social_has_permission_access_servicio_social_panel(): void
    {
        $servicio = User::where('email', 'servicio@tecnm.edu.mx')->first();
        $this->assertTrue($servicio->hasPermissionTo(RoleEnum::PERM_ACCESS_SERVICIO));
    }

    public function test_admin_can_access_admin_panel(): void
    {
        $admin = User::where('email', 'admin@tecnm.edu.mx')->first();
        $panel = filament()->getPanel('admin');

        $this->assertTrue($admin->canAccessPanel($panel));
    }

    public function test_servicio_social_can_access_servicio_social_panel(): void
    {
        $servicio = User::where('email', 'servicio@tecnm.edu.mx')->first();
        $panel = filament()->getPanel('servicio-social');

        $this->assertTrue($servicio->canAccessPanel($panel));
    }

    public function test_admin_cannot_access_servicio_social_panel(): void
    {
        $admin = User::where('email', 'admin@tecnm.edu.mx')->first();
        $panel = filament()->getPanel('servicio-social');

        $this->assertFalse($admin->canAccessPanel($panel));
    }

    public function test_servicio_social_cannot_access_admin_panel(): void
    {
        $servicio = User::where('email', 'servicio@tecnm.edu.mx')->first();
        $panel = filament()->getPanel('admin');

        $this->assertFalse($servicio->canAccessPanel($panel));
    }
}
