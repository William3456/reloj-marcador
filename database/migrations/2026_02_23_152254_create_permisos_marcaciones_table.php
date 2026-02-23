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
        Schema::create('permisos_marcaciones', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_marcacion');
            $table->unsignedBigInteger('id_permiso');

            $table->timestamps();

            // Foreign Keys (opcional pero recomendado)
            $table->foreign('id_marcacion')
                  ->references('id')
                  ->on('marcaciones_empleado')
                  ->onDelete('cascade');

            $table->foreign('id_permiso')
                  ->references('id')
                  ->on('permiso_trabajador')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permisos_marcaciones');
    }
};
