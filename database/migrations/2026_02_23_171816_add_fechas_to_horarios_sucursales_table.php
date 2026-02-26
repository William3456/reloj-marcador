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
        Schema::table('horarios_sucursales', function (Blueprint $table) {
            $table->date('fecha_inicio')->nullable()->after('id_horario_historico');
            $table->date('fecha_fin')->nullable()->after('fecha_inicio');
            $table->boolean('es_actual')->default(true)->after('fecha_fin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('horarios_sucursales', function (Blueprint $table) {
            //
        });
    }
};
