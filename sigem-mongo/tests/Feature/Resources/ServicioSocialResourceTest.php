<?php

namespace Tests\Feature\Resources;

use App\Filament\ServicioSocial\Resources\AreaResource;
use App\Filament\ServicioSocial\Resources\InventarioResource;
use App\Filament\ServicioSocial\Resources\MantenimientoResource;
use App\Filament\ServicioSocial\Resources\MaterialResource;
use App\Filament\ServicioSocial\Resources\ReceptorResource;
use App\Filament\ServicioSocial\Resources\SolicitudResource;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicioSocialUserSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicioSocialResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AdminUserSeeder::class);
        $this->seed(ServicioSocialUserSeeder::class);
    }

    private function getServicioSocialUser()
    {
        return User::where('email', 'servicio@tecnm.edu.mx')->first();
    }

    public function test_servicio_social_can_access_areas_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('servicio-social'));
        $this->actingAs($this->getServicioSocialUser())
            ->get(AreaResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_servicio_social_can_access_materiales_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('servicio-social'));
        $this->actingAs($this->getServicioSocialUser())
            ->get(MaterialResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_servicio_social_can_access_inventarios_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('servicio-social'));
        $this->actingAs($this->getServicioSocialUser())
            ->get(InventarioResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_servicio_social_can_access_solicitudes_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('servicio-social'));
        $this->actingAs($this->getServicioSocialUser())
            ->get(SolicitudResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_servicio_social_can_access_mantenimientos_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('servicio-social'));
        $this->actingAs($this->getServicioSocialUser())
            ->get(MantenimientoResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_servicio_social_can_access_receptores_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('servicio-social'));
        $this->actingAs($this->getServicioSocialUser())
            ->get(ReceptorResource::getUrl('index'))
            ->assertSuccessful();
    }
}
