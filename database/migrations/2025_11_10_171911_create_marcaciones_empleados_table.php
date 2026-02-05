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
        Schema::create('marcaciones_empleados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_empleado');
            $table->unsignedBigInteger('id_sucursal');
            $table->decimal('latitud', 10, 7);
            $table->decimal('longitud', 10, 7);
            $table->integer('distancia_real_mts')->nullable();
            $table->string('ubicacion', 250)->nullable();
            $table->integer('tipo_marcacion'); // 1-Entrada, 2-Salida
            $table->string('ubi_foto', 250)->nullable();
            $table->string('ubi_foto_full', 250)->nullable();
            $table->unsignedBigInteger('id_permiso_Aplicado')->nullable;
            $table->integer('fuera_horario')->nullable;
            $table->integer('id_marcacion_entrada')->nullable;
            $table->unsignedBigInteger('id_horario')->nullable;
            $table->unsignedBigInteger('id_horario_historico_empleado')->nullable;
            $table->unsignedBigInteger('id_horario_historico_sucursal')->nullable;
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marcaciones_empleados');
    }
};
