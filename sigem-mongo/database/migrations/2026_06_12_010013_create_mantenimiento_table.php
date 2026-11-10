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
        Schema::create('mantenimiento', function (Blueprint $table) {
            $table->id('id_mantenimiento');
            $table->foreignId('id_inventario')->constrained('inventario', 'id_inventario')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('id_usuario_solicita')->constrained('users', 'id')->onDelete('restrict')->onUpdate('cascade');
            $table->string('nombre_tecnico', 150);
            $table->string('num_control_tecnico', 10)->nullable();
            $table->enum('tipo_servicio', ['Servicio Social', 'Prácticas Profesionales', 'Personal Técnico'])->default('Servicio Social');
            $table->enum('tipo_mantenimiento', ['Preventivo', 'Correctivo', 'Mejora']);
            $table->text('descripcion_falla')->nullable();
            $table->text('descripcion_trabajo')->nullable();
            $table->date('fecha_solicitud');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->enum('estado', ['Solicitado', 'En proceso', 'Pendiente Revision Admin', 'Completado', 'Cancelado'])->default('Solicitado');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mantenimiento');
    }
};
