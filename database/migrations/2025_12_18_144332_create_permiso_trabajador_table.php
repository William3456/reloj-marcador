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
Schema::create('permiso_trabajador', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_empleado');
            $table->unsignedBigInteger('id_tipo_permiso');

            $table->string('motivo')->nullable();

            // Para permisos de geolocalización
            $table->integer('cantidad_mts')->nullable();
            
            //Se interpreta como minutos o horas según el tipo de permiso 
            $table->integer('valor')->nullable();

            // Para permisos por rango de fechas
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();

            // Para permisos por X días hábiles
            $table->integer('dias_activa')->nullable();

            $table->boolean('estado')->default(true);

            $table->timestamps();

            // FK
            $table->foreign('id_empleado')
                ->references('id')
                ->on('empleados')
                ->onDelete('cascade');

            $table->foreign('id_tipo_permiso')
                ->references('id')
                ->on('tipos_permiso');
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permiso_trabajador');
    }
};
