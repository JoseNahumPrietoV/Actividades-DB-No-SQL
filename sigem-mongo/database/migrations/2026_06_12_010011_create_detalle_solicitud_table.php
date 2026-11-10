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
        Schema::create('detalle_solicitud', function (Blueprint $table) {
            $table->id('id_detalle');
            $table->integer('cantidad')->default(1);
            $table->foreignId('id_solicitud')->constrained('solicitud', 'id_solicitud')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('id_producto')->constrained('material', 'id_producto')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('id_inventario')->nullable()->constrained('inventario', 'id_inventario')->onDelete('set null')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_solicitud');
    }
};
