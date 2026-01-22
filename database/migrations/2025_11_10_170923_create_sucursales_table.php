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
        Schema::create('sucursales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('direccion', 250)->nullable();
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->string('correo_encargado', length: 150)->nullable();
            $table->string('telefono', 10)->nullable();
            $table->unsignedBigInteger('id_empresa');
            $table->unsignedBigInteger('id_horario')->nullable();
            $table->integer('cant_empleados')->default(0);
            $table->integer('rango_marcacion_mts')->default(30);
            $table->integer('margen_error_gps_mts')->default(value: 30);
            $table->json('dias_laborales')->nullable(); // ["LUN","MAR","MIE"]
            $table->integer('estado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sucursales');
    }
};
