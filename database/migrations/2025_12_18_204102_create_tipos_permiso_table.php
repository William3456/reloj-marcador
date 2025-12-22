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
        Schema::create('tipos_permiso', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 50)->unique();
            $table->string('nombre', 100);

            $table->boolean('requiere_distancia')->default(false)->comment('Indica si el permiso requiere especificar una distancia de marcación fuera de la de la sucursal');
            $table->boolean('requiere_fechas')->default(false)->comment('Indica si el permiso requiere especificar fechas de inicio y fin');
            $table->boolean('requiere_dias')->default(false)->comment('Indica si el permiso requiere especificar días hábiles laborales a partir del actual');

            $table->boolean('estado')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_permiso');
    }
};
