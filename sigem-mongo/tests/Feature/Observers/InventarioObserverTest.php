<?php

namespace Tests\Feature\Observers;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventarioObserverTest extends TestCase
{
    use RefreshDatabase;

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
            'requiere_control_individual' => 1,
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

    public function test_creating_inventario_increments_material_stock(): void
    {
        $material = $this->createMaterial();
        $area = $this->createArea();
        $user = $this->getUser();

        $this->assertEquals(10, $material->stock_actual);

        Inventario::create([
            'numero_serie' => 'SN-12345',
            'id_producto' => $material->id_producto,
            'id_area' => $area->id_area,
            'estado' => 'Disponible',
            'tipo_propiedad' => 'Propio',
            'id_usuario' => $user->id,
            'fecha_registro' => now(),
        ]);

        $this->assertEquals(11, $material->fresh()->stock_actual);
    }

    public function test_deleting_inventario_decrements_material_stock(): void
    {
        $material = $this->createMaterial();
        $area = $this->createArea();
        $user = $this->getUser();

        $inventario = Inventario::create([
            'numero_serie' => 'SN-12345',
            'id_producto' => $material->id_producto,
            'id_area' => $area->id_area,
            'estado' => 'Disponible',
            'tipo_propiedad' => 'Propio',
            'id_usuario' => $user->id,
            'fecha_registro' => now(),
        ]);

        $this->assertEquals(11, $material->fresh()->stock_actual);

        $inventario->delete();

        $this->assertEquals(10, $material->fresh()->stock_actual);
    }

    public function test_changing_material_updates_both_stocks(): void
    {
        $material1 = $this->createMaterial();
        $material2 = $this->createMaterial();
        $area = $this->createArea();
        $user = $this->getUser();

        $inventario = Inventario::create([
            'numero_serie' => 'SN-12345',
            'id_producto' => $material1->id_producto,
            'id_area' => $area->id_area,
            'estado' => 'Disponible',
            'tipo_propiedad' => 'Propio',
            'id_usuario' => $user->id,
            'fecha_registro' => now(),
        ]);

        $this->assertEquals(11, $material1->fresh()->stock_actual);
        $this->assertEquals(10, $material2->fresh()->stock_actual);

        // Change product (reload to clear cached material relation)
        $inventario = $inventario->fresh();
        $inventario->update(['id_producto' => $material2->id_producto]);

        $this->assertEquals(10, $material1->fresh()->stock_actual);
        $this->assertEquals(11, $material2->fresh()->stock_actual);
    }

    public function test_changing_estado_to_baja_sets_fecha_baja(): void
    {
        $material = $this->createMaterial();
        $area = $this->createArea();
        $user = $this->getUser();

        $inventario = Inventario::create([
            'numero_serie' => 'SN-12345',
            'id_producto' => $material->id_producto,
            'id_area' => $area->id_area,
            'estado' => 'Disponible',
            'tipo_propiedad' => 'Propio',
            'id_usuario' => $user->id,
            'fecha_registro' => now(),
        ]);

        $this->assertNull($inventario->fecha_baja);

        $inventario->update(['estado' => 'Baja']);

        $this->assertNotNull($inventario->fecha_baja);
    }
}
