<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventario', function (Blueprint $table) {
            $table->id('id_inventario');
            $table->string('num_serie', 100)->nullable();
            $table->foreignId('id_producto')->constrained('material', 'id_producto')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('id_usuario')->constrained('users', 'id')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('id_proveedor')->nullable()->constrained('proveedores', 'id_proveedor')->onDelete('set null')->onUpdate('cascade');
            $table->enum('estado', ['Disponible', 'Asignado', 'En Mantenimiento', 'Dañado', 'Baja', 'Devuelto a Proveedor'])->default('Disponible');
            $table->enum('estado_registro', ['Pendiente', 'Aprobado', 'Rechazado'])->default('Pendiente');
            $table->enum('tipo_propiedad', ['Propio', 'Rentado'])->default('Propio');
            $table->string('ubicacion_fisica', 150)->nullable();
            $table->date('fecha_registro');
            $table->date('fecha_factura')->nullable();
            $table->string('num_factura', 100)->nullable();
            $table->date('fecha_baja')->nullable();
            $table->date('fecha_inicio_renta')->nullable();
            $table->date('fecha_fin_renta')->nullable();
            $table->text('observaciones_renta')->nullable();
            $table->text('observaciones_generales')->nullable();
            $table->date('garantia_fecha_fin')->nullable();
            $table->enum('garantia_estado', ['vigente', 'vencida', 'sin_garantia'])->default('sin_garantia');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario');
    }
};
