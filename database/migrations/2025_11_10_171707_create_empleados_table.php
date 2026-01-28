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
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->string('cod_trabajador', 20)->comment('Formado por ID+IDSuc.+Iniciales');
            $table->string('correo', length: 150)->unique();
            $table->string('direccion', 200);
            $table->date('fecha_nacimiento');
            $table->string('documento', 200)->unique();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->unsignedBigInteger('id_puesto');
            $table->unsignedBigInteger('id_depto');
            $table->unsignedBigInteger('id_sucursal');
            $table->unsignedBigInteger('id_empresa');
            $table->unsignedBigInteger('creado_por_usuario');
            $table->integer('login')->default(0); // Para logearse en la app de empleados
            $table->integer('estado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
