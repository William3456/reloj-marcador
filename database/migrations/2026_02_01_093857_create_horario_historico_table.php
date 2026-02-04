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
        Schema::create('horario_historico', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_horario');

            $table->time('hora_entrada');
            $table->time('hora_salida');

            $table->integer('tipo_horario')->default(0)->comment('1=sucursal,0=empleado');
            $table->integer('tolerancia')->default(0);

            $table->dateTime('vigente_desde');
            $table->dateTime('vigente_hasta')->nullable();
            $table->json('dias')->nullable();
            $table->timestamps();
            $table->foreign('id_horario')
                ->references('id')
                ->on('horarios');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horario_historico');
    }
};
