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
        Schema::create('horarios_sucursales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_sucursal');
            $table->unsignedBigInteger('id_horario');
            $table->foreign('id_sucursal')
                  ->references('id')->on('sucursales')
                  ->onDelete('cascade');

            $table->foreign('id_horario')
                  ->references('id')->on('horarios')
                  ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horarios_sucursales');
    }
};
