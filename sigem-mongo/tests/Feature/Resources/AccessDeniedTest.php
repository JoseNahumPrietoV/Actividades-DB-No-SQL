<?php

namespace Tests\Feature\Resources;

use App\Filament\Resources\AreaResource;
use App\Filament\Resources\DepartamentoResource;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicioSocialUserSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessDeniedTest extends TestCase
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

    public function test_servicio_social_cannot_access_admin_areas_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getServicioSocialUser())
            ->get(AreaResource::getUrl('index'))
            ->assertForbidden(); // Usually Filament returns 403 Forbidden for unauthorized resources
    }

    public function test_servicio_social_cannot_access_admin_departamentos_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getServicioSocialUser())
            ->get(DepartamentoResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_servicio_social_cannot_access_admin_users_resource()
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->getServicioSocialUser())
            ->get(UserResource::getUrl('index'))
            ->assertForbidden();
    }
}
