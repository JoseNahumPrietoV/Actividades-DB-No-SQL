<?php

namespace Tests\Feature\ErrorPages;

use App\Filament\Resources\DepartamentoResource;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicioSocialUserSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AdminUserSeeder::class);
        $this->seed(ServicioSocialUserSeeder::class);
    }

    public function test_403_page_is_shown_for_unauthorized_access_and_has_correct_link()
    {
        $ssUser = User::where('email', 'servicio@tecnm.edu.mx')->first();

        // Accessing admin panel route that requires admin permission
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $response = $this->actingAs($ssUser)->get(DepartamentoResource::getUrl('index'));

        $response->assertStatus(403);
        $response->assertSee('No tienes permiso para acceder a esta sección.');

        // Since it's Servicio Social user, it should show /servicio-social/login
        $response->assertSee('/servicio-social/login');
    }

    public function test_403_page_shows_admin_login_link_for_admin_user()
    {
        Route::get('/test-403-admin', function () {
            abort(403);
        })->middleware('web');

        $admin = User::where('email', 'admin@tecnm.edu.mx')->first();
        $response = $this->actingAs($admin)->get('/test-403-admin');

        $response->assertStatus(403);
        $response->assertSee('/admin/login');
    }

    public function test_403_page_shows_admin_login_link_for_guests()
    {
        Route::get('/test-403-guest', function () {
            abort(403);
        })->middleware('web');

        $response = $this->get('/test-403-guest');

        $response->assertStatus(403);
        $response->assertSee('/admin/login');
    }

    public function test_419_page_is_shown_on_token_mismatch()
    {
        Route::post('/test-419', function () {
            throw new TokenMismatchException;
        })->middleware('web');

        $response = $this->post('/test-419');

        $response->assertStatus(419);
        $response->assertSee('Tu sesión ha expirado');
        $response->assertSee('/admin/login');
    }
}
