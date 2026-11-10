<?php

namespace Tests\Feature\Observers;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\DetalleSolicitud;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\Receptor;
use App\Models\Solicitud;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SolicitudObserverTest extends TestCase
{
    use RefreshDatabase;

    private function getUser()
    {
        return User::firstOrCreate([
            'email' => 'test@test.com',
        ], [
            'name' => 'Test',
            'password' => bcrypt('password'),
            'tipo_usuario' => 'Administrador',
        ]);
    }

    private function createArea(): Area
    {
        $depto = Departamento::firstOrCreate(['nombre' => 'Sistemas']);

        return Area::firstOrCreate([
            'nombre' => 'Laboratorio',
            'id_departamento' => $depto->id_departamento,
        ]);
    }

    private function createMaterial(): Material
    {
        $unidad = UnidadMedida::firstOrCreate(['nombre' => 'Pza']);
        $marca = MarcaMaterial::firstOrCreate(['nombre' => 'Genérico']);
        $tipo = TipoMaterial::firstOrCreate(['nombre' => 'Consumible']);

        return Material::create([
            'nombre' => 'Laptop '.uniqid(),
            'id_unidad' => $unidad->id_unidad,
            'id_marca' => $marca->id_marca,
            'id_tipodematerial' => $tipo->id_tipodematerial,
            'stock_actual' => 10,
            'requiere_control_individual' => 0,
        ]);
    }

    private function createReceptor(): Receptor
    {
        $area = $this->createArea();

        return Receptor::create([
            'nombre' => 'Juan',
            'apellido_paterno' => 'Perez',
            'apellido_materno' => 'Gomez',
            'tipo_receptor' => 'Docente',
            'id_area' => $area->id_area,
        ]);
    }

    public function test_authorizing_solicitud_decrements_material_stock(): void
    {
        $material = $this->createMaterial();
        $receptor = $this->createReceptor();
        $user = $this->getUser();

        $solicitud = Solicitud::create([
            'id_receptor' => $receptor->id_receptor,
            'tipo_movimiento' => 'Asignacion Permanente',
            'estado' => 'Pendiente',
            'id_usuario' => $user->id,
            'fecha_solicitud' => now(),
        ]);

        DetalleSolicitud::create([
            'id_solicitud' => $solicitud->id_solicitud,
            'id_producto' => $material->id_producto,
            'cantidad' => 2,
        ]);

        $this->assertEquals(10, $material->fresh()->stock_actual);

        $solicitud->update(['estado' => 'Autorizado']);

        $this->assertEquals(8, $material->fresh()->stock_actual);
    }

    public function test_rejecting_authorized_solicitud_restores_material_stock(): void
    {
        $material = $this->createMaterial();
        $receptor = $this->createReceptor();
        $user = $this->getUser();

        $solicitud = Solicitud::create([
            'id_receptor' => $receptor->id_receptor,
            'tipo_movimiento' => 'Asignacion Permanente',
            'estado' => 'Pendiente',
            'id_usuario' => $user->id,
            'fecha_solicitud' => now(),
        ]);

        DetalleSolicitud::create([
            'id_solicitud' => $solicitud->id_solicitud,
            'id_producto' => $material->id_producto,
            'cantidad' => 2,
        ]);

        $solicitud->update(['estado' => 'Autorizado']);
        $this->assertEquals(8, $material->fresh()->stock_actual);

        $solicitud->update(['estado' => 'Rechazado']);

        $this->assertEquals(10, $material->fresh()->stock_actual);
    }

    public function test_rejecting_pending_solicitud_does_not_change_stock(): void
    {
        $material = $this->createMaterial();
        $receptor = $this->createReceptor();
        $user = $this->getUser();

        $solicitud = Solicitud::create([
            'id_receptor' => $receptor->id_receptor,
            'tipo_movimiento' => 'Asignacion Permanente',
            'estado' => 'Pendiente',
            'id_usuario' => $user->id,
            'fecha_solicitud' => now(),
        ]);

        DetalleSolicitud::create([
            'id_solicitud' => $solicitud->id_solicitud,
            'id_producto' => $material->id_producto,
            'cantidad' => 2,
        ]);

        $this->assertEquals(10, $material->fresh()->stock_actual);

        $solicitud->update(['estado' => 'Rechazado']);

        $this->assertEquals(10, $material->fresh()->stock_actual);
    }
}
