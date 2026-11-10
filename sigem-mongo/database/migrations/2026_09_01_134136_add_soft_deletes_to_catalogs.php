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
        Schema::table('proveedores', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('marca_material', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('tipo_material', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('unidad_medida', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('marca_material', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('tipo_material', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('unidad_medida', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
