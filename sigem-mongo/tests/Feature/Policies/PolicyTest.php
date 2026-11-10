<?php

namespace Tests\Feature\Policies;

use App\Models\Area;
use App\Models\Inventario;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicioSocialUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AdminUserSeeder::class);
        $this->seed(ServicioSocialUserSeeder::class);
    }

    public function test_admin_can_perform_all_actions_on_resources(): void
    {
        $admin = User::where('email', 'admin@tecnm.edu.mx')->first();

        // Admin Resource (e.g. Area)
        $area = new Area;
        $this->assertTrue($admin->can('viewAny', Area::class));
        $this->assertTrue($admin->can('create', Area::class));
        $this->assertTrue($admin->can('update', $area));
        $this->assertTrue($admin->can('delete', $area));

        // Shared Resource (e.g. Inventario)
        $inventario = new Inventario;
        $this->assertTrue($admin->can('viewAny', Inventario::class));
        $this->assertTrue($admin->can('create', Inventario::class));
        $this->assertTrue($admin->can('update', $inventario));
        $this->assertTrue($admin->can('delete', $inventario));
    }

    public function test_servicio_social_can_view_shared_resources(): void
    {
        $servicio = User::where('email', 'servicio@tecnm.edu.mx')->first();

        // Shared Resource (Inventario)
        $this->assertTrue($servicio->can('viewAny', Inventario::class));
    }

    public function test_servicio_social_can_create_but_not_update_delete_shared_resources(): void
    {
        $servicio = User::where('email', 'servicio@tecnm.edu.mx')->first();

        // Shared Resource (Inventario)
        $inventario = new Inventario;
        $this->assertTrue($servicio->can('create', Inventario::class));
        $this->assertFalse($servicio->can('update', $inventario));
        $this->assertFalse($servicio->can('delete', $inventario));
    }
}
