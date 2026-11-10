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
        Schema::create('historial_estados', function (Blueprint $table) {
            $table->id('id_historial');
            $table->foreignId('id_inventario')->constrained('inventario', 'id_inventario')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('id_usuario')->constrained('users', 'id')->onDelete('restrict')->onUpdate('cascade');
            $table->string('estado_anterior', 50)->nullable();
            $table->string('estado_nuevo', 50);
            $table->text('motivo_cambio')->nullable();
            $table->string('ubicacion_fisica', 255)->nullable();
            $table->timestamp('fecha_cambio')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_estados');
    }
};
