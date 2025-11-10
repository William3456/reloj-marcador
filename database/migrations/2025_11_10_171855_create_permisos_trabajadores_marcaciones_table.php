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
        Schema::create('permisos_trabajadores_marcaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_empleado');
            $table->string('motivo', 200)->nullable();
            $table->integer('cantidad_mts')->default(0);
            $table->integer('dias_activa')->nullable(); // ej: días laborales en lo que estará activo el permiso
            $table->integer('estado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permisos_trabajadores_marcaciones');
    }
};
