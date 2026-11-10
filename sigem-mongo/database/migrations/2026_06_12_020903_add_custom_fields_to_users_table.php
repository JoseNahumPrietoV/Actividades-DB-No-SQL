<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('apellido_paterno', 100)->nullable()->after('name');
            $table->string('apellido_materno', 100)->nullable()->after('apellido_paterno');
            $table->string('num_control', 10)->unique()->nullable()->after('apellido_materno');
            $table->string('carrera', 100)->nullable()->after('num_control');
            $table->char('RFC', 13)->unique()->nullable()->after('carrera');
            $table->enum('tipo_usuario', ['Administrador', 'Servicio', 'Pendiente'])->default('Pendiente')->after('RFC');
            $table->boolean('activo')->default(true)->after('tipo_usuario');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['apellido_paterno', 'apellido_materno', 'num_control', 'carrera', 'RFC', 'tipo_usuario', 'activo']);
        });
    }
};
