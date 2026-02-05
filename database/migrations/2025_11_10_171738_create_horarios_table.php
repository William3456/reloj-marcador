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
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('turno'); // Relacionada con tabla turno 
            $table->string('turno_txt',100); 
            $table->time('hora_ini');
            $table->time('hora_fin');
            $table->json('dias')->nullable(); // ["LUN","MAR","MIE"]
            $table->integer('permitido_marcacion')->default(1); //(0=No, En este caso es para empleados 1= Sí, En este caso es para lapsos en los cuales la sucursal permite marcacion por el empleado)
            $table->integer('estado')->default(1);
            $table->integer('tolerancia_minutos');
            $table->integer('requiere_salida')->comment('0=No, 1=Sí');
            $table->unsignedBigInteger('sucursal_creacion')->comment('donde se creo el horario');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horarios');
    }
};
