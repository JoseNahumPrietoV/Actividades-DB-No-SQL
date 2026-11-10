<?php

namespace App\Providers;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\Proveedor;
use App\Models\Receptor;
use App\Models\Solicitud;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use App\Models\User;
use App\Observers\InventarioObserver;
use App\Observers\MantenimientoObserver;
use App\Observers\SolicitudObserver;
use App\Policies\AreaPolicy;
use App\Policies\DepartamentoPolicy;
use App\Policies\InventarioPolicy;
use App\Policies\MantenimientoPolicy;
use App\Policies\MarcaMaterialPolicy;
use App\Policies\MaterialPolicy;
use App\Policies\ProveedorPolicy;
use App\Policies\ReceptorPolicy;
use App\Policies\SolicitudPolicy;
use App\Policies\TipoMaterialPolicy;
use App\Policies\UnidadMedidaPolicy;
use App\Policies\UserPolicy;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        app()->setLocale('es');

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Inventario::class, InventarioPolicy::class);
        Gate::policy(Solicitud::class, SolicitudPolicy::class);
        Gate::policy(Mantenimiento::class, MantenimientoPolicy::class);
        Gate::policy(Departamento::class, DepartamentoPolicy::class);
        Gate::policy(Area::class, AreaPolicy::class);
        Gate::policy(Material::class, MaterialPolicy::class);
        Gate::policy(MarcaMaterial::class, MarcaMaterialPolicy::class);
        Gate::policy(TipoMaterial::class, TipoMaterialPolicy::class);
        Gate::policy(UnidadMedida::class, UnidadMedidaPolicy::class);
        Gate::policy(Proveedor::class, ProveedorPolicy::class);
        Gate::policy(Receptor::class, ReceptorPolicy::class);

        Inventario::observe(InventarioObserver::class);
        Mantenimiento::observe(MantenimientoObserver::class);
        Solicitud::observe(SolicitudObserver::class);

        Event::listen(function (Attempting $event) {
            $panel = Filament::getCurrentPanel();
            if (! $panel) {
                return;
            }

            $user = User::where('email', $event->credentials['email'] ?? '')->first();

            if ($user && ! $user->canAccessPanel($panel)) {
                throw ValidationException::withMessages([
                    'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
                ]);
            }
        });
    }
}
