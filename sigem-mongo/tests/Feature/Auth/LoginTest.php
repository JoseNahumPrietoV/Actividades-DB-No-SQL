<?php

namespace Tests\Feature\Auth;

use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicioSocialUserSeeder;
use Filament\Facades\Filament;
use Filament\Pages\Auth\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AdminUserSeeder::class);
        $this->seed(ServicioSocialUserSeeder::class);
    }

    public function test_admin_can_login_to_admin_panel(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'admin@tecnm.edu.mx',
                'password' => 'admin123',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect('/admin');

        $this->assertAuthenticated();
    }

    public function test_servicio_social_can_login_to_servicio_social_panel(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('servicio-social'));

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'servicio@tecnm.edu.mx',
                'password' => 'servicio123',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect('/servicio-social');

        $this->assertAuthenticated();
    }

    public function test_admin_cannot_login_to_servicio_social_panel(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('servicio-social'));

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'admin@tecnm.edu.mx',
                'password' => 'admin123',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }

    public function test_servicio_social_cannot_login_to_admin_panel(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'servicio@tecnm.edu.mx',
                'password' => 'servicio123',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }

    public function test_incorrect_credentials_show_error(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'admin@tecnm.edu.mx',
                'password' => 'wrongpassword',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }
}
