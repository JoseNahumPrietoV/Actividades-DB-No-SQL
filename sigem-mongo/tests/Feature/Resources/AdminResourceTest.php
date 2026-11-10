<?php

namespace Tests\Feature\Resources;

use App\Filament\Resources\AreaResource;
use App\Filament\Resources\DepartamentoResource;
use App\Filament\Resources\InventarioResource;
use App\Filament\Resources\MantenimientoResource;
use App\Filament\Resources\MaterialResource;
use App\Filament\Resources\ProveedorResource;
use App\Filament\Resources\ReceptorResource;
use App\Filament\Resources\SolicitudResource;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicioSocialUserSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AdminUserSeeder::class);
        $this->seed(ServicioSocialUserSeeder::class);
    }

    private function getAdminUser()
    {
        return User::where('email', 'admin@tecnm.edu.mx')->first();
    }

    public function test_admin_can_access_areas_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser())
            ->get(AreaResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_admin_can_access_departamentos_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser())
            ->get(DepartamentoResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_admin_can_access_materiales_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser())
            ->get(MaterialResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_admin_can_access_inventarios_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser())
            ->get(InventarioResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_admin_can_access_solicitudes_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser())
            ->get(SolicitudResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_admin_can_access_mantenimientos_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser())
            ->get(MantenimientoResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_admin_can_access_proveedores_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser())
            ->get(ProveedorResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_admin_can_access_receptores_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser())
            ->get(ReceptorResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_admin_can_access_users_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getAdminUser())
            ->get(UserResource::getUrl('index'))
            ->assertSuccessful();
    }
}
