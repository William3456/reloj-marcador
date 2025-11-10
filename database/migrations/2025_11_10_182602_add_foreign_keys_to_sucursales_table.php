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
        Schema::table('sucursales', function (Blueprint $table) {
            $table->foreign('id_empresa')
                ->references('id')->on('empresas')
                ->onDelete('cascade');

            $table->foreign('id_horario')
                ->references('id')->on('horarios')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sucursales', function (Blueprint $table) {
            $table->dropForeign(['id_empresa']);
            $table->dropForeign(['id_horario']);
        });
    }
};
