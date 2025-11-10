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
        Schema::table('empleados', function (Blueprint $table) {
            $table->foreign('id_puesto')
                ->references('id')->on('puestos_trabajos')
                ->onDelete('cascade');

            $table->foreign('id_depto')
                ->references('id')->on('departamentos')
                ->onDelete('cascade');

            $table->foreign('id_sucursal')
                ->references('id')->on('sucursales')
                ->onDelete('cascade');

            $table->foreign('id_empresa')
                ->references('id')->on('empresas')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropForeign(['id_puesto']);
            $table->dropForeign(['id_depto']);
            $table->dropForeign(['id_sucursal']);
            $table->dropForeign(['id_empresa']);
        });
    }
};
