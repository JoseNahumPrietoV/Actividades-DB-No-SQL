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
        Schema::create('material', function (Blueprint $table) {
            $table->id('id_producto');
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->string('modelo', 100)->nullable();
            $table->foreignId('id_unidad')->constrained('unidad_medida', 'id_unidad')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('id_marca')->constrained('marca_material', 'id_marca')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('id_tipodematerial')->constrained('tipo_material', 'id_tipodematerial')->onDelete('restrict')->onUpdate('cascade');
            $table->boolean('requiere_control_individual')->default(true);
            $table->integer('stock_actual')->default(0);
            $table->integer('stock_minimo')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material');
    }
};
