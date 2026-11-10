<?php

namespace App\Filament\ServicioSocial\Pages;

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
use Filament\Pages\Dashboard as BaseDashboard;
use Spatie\Activitylog\Models\Activity;

class Dashboard extends BaseDashboard
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Panel';

    protected static ?string $navigationGroup = 'Panel';

    protected static ?string $title = 'SIGEM - Servicio Social';

    protected static string $view = 'filament.servicio-social.pages.dashboard';

    public function getColumns(): int|string|array
    {
        return [
            'default' => 12,
            'sm' => 12,
            'md' => 12,
            'lg' => 12,
            'xl' => 12,
            '2xl' => 12,
        ];
    }

    public function getViewData(): array
    {
        $userId = auth()->id();

        // 1. Welcome Card Stats (Specific for SS as requested)
        $misSolicitudes = Solicitud::where('id_usuario', $userId)->count();
        $misSolicitudesPendientes = Solicitud::where('id_usuario', $userId)->pendientes()->count();
        $misMantenimientos = Mantenimiento::where('id_usuario_solicita', $userId)->count();
        $totalActivos = Inventario::count();

        // Bitacora activity just for this user
        // NOTA: Spatie Activitylog requiere PDO SQL.
        $actividadReciente = collect([]);

        // Colecciones limitadas (mini tablas) y completas (modales)
        $inventariosCompletos = Inventario::with(['material', 'material.marca', 'material.tipo'])->latest('created_at')->take(50)->get();
        $inventariosRecientes = $inventariosCompletos->take(3);

        $solicitudesCompletas = Solicitud::with('usuario')->where('id_usuario', $userId)->latest('created_at')->take(50)->get();
        $solicitudesRecientes = $solicitudesCompletas->where('estado', 'Pendiente')->take(3);
        if ($solicitudesRecientes->isEmpty()) {
            $solicitudesRecientes = $solicitudesCompletas->take(3);
        }

        $mantenimientosCompletos = Mantenimiento::with(['inventario', 'inventario.material', 'usuarioSolicita'])->where('id_usuario_solicita', $userId)->latest('created_at')->take(50)->get();
        $mantenimientosRecientes = $mantenimientosCompletos->whereIn('estado', ['Pendiente', 'Solicitado', 'Pendiente Revision Admin', 'En proceso'])->take(3);
        if ($mantenimientosRecientes->isEmpty()) {
            $mantenimientosRecientes = $mantenimientosCompletos->take(3);
        }

        $inventarioDisponibles = Inventario::disponibles()->count();
        $inventarioAsignados = Inventario::asignados()->count();
        $inventarioEnMantenimiento = Inventario::enMantenimiento()->count();
        $inventarioDanados = Inventario::danados()->count();

        $mantenimientoEnRevision = Mantenimiento::where('id_usuario_solicita', $userId)->enRevision()->count();
        $mantenimientoEnProceso = Mantenimiento::where('id_usuario_solicita', $userId)->enProceso()->count();
        $mantenimientoCompletados = Mantenimiento::where('id_usuario_solicita', $userId)->completados()->count();

        $solicitudesAutorizadas = Solicitud::where('id_usuario', $userId)->autorizadas()->count();
        $solicitudesRechazadas = Solicitud::where('id_usuario', $userId)->rechazadas()->count();

        // Catálogos Completos (Read-only for SS)
        $departamentosList = Departamento::latest()->take(50)->get();
        $materialesList = Material::with(['tipo', 'unidad', 'marca'])->latest()->take(50)->get();
        $areasList = Area::with('departamento')->latest()->take(50)->get();
        $marcasList = MarcaMaterial::latest()->take(50)->get();
        $tiposList = TipoMaterial::latest()->take(50)->get();
        $unidadesList = UnidadMedida::latest()->take(50)->get();
        $proveedoresList = Proveedor::latest()->take(50)->get();
        $receptoresList = Receptor::with('area.departamento')->latest()->take(50)->get();
        $usuariosList = User::latest()->take(50)->get();

        $totalRegistrosCatalogos = $departamentosList->count() + $materialesList->count() + $areasList->count() +
                                   $marcasList->count() + $tiposList->count() + $unidadesList->count() +
                                   $proveedoresList->count() + $receptoresList->count() + $usuariosList->count();

        return [
            'misSolicitudes' => $misSolicitudes,
            'misSolicitudesPendientes' => $misSolicitudesPendientes,
            'misMantenimientos' => $misMantenimientos,
            'totalActivos' => $totalActivos,

            'actividadReciente' => $actividadReciente,
            'inventariosRecientes' => $inventariosRecientes,
            'solicitudesRecientes' => $solicitudesRecientes,
            'mantenimientosRecientes' => $mantenimientosRecientes,

            // Variables para módulos
            'inventarioDisponibles' => $inventarioDisponibles,
            'inventarioAsignados' => $inventarioAsignados,
            'inventarioEnMantenimiento' => $inventarioEnMantenimiento,
            'inventarioDanados' => $inventarioDanados,

            'mantenimientoEnRevision' => $mantenimientoEnRevision,
            'mantenimientoEnProceso' => $mantenimientoEnProceso,
            'mantenimientoCompletados' => $mantenimientoCompletados,

            'solicitudesAutorizadas' => $solicitudesAutorizadas,
            'solicitudesRechazadas' => $solicitudesRechazadas,

            'inventariosCompletos' => $inventariosCompletos,
            'solicitudesCompletas' => $solicitudesCompletas,
            'mantenimientosCompletos' => $mantenimientosCompletos,

            'departamentosList' => $departamentosList,
            'materialesList' => $materialesList,
            'areasList' => $areasList,
            'marcasList' => $marcasList,
            'tiposList' => $tiposList,
            'unidadesList' => $unidadesList,
            'proveedoresList' => $proveedoresList,
            'receptoresList' => $receptoresList,
            'usuariosList' => $usuariosList,

            'totalRegistrosCatalogos' => $totalRegistrosCatalogos,
        ];
    }
}
