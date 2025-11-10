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
        Schema::table('horarios_trabajadores', function (Blueprint $table) {
            $table->foreign('id_empleado')
                ->references('id')->on('empleados')
                ->onDelete('cascade');

            $table->foreign('id_horario')
                ->references('id')->on('horarios')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('horarios_trabajadores', function (Blueprint $table) {
            $table->dropForeign(['id_empleado']);
            $table->dropForeign(['id_horario']);
        });
    }
};
