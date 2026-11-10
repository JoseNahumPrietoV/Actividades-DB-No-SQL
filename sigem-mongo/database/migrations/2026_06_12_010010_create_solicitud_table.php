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
        Schema::create('solicitud', function (Blueprint $table) {
            $table->id('id_solicitud');
            $table->date('fecha_solicitud');
            $table->text('observaciones')->nullable();
            $table->date('fecha_autorizacion')->nullable();
            $table->foreignId('autorizado_por')->nullable()->constrained('users', 'id')->onDelete('set null')->onUpdate('cascade');
            $table->enum('estado', ['Pendiente', 'Autorizado', 'Rechazado', 'Completado', 'Cancelado'])->default('Pendiente');
            $table->date('fecha_devolucion_estimada')->nullable();
            $table->date('fecha_devolucion_real')->nullable();
            $table->foreignId('id_usuario')->constrained('users', 'id')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('id_receptor')->constrained('receptor', 'id_receptor')->onDelete('restrict')->onUpdate('cascade');
            $table->enum('tipo_movimiento', ['Asignacion Temporal', 'Asignacion Permanente', 'Renta Externa'])->default('Asignacion Temporal');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud');
    }
};
