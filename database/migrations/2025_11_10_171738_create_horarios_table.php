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
            $table->integer('turno'); // 1-Mañana, 2-Tarde, 3-Noche, 4 madrugrada
            $table->time('hora_ini');
            $table->time('hora_fin');
            $table->integer('permitido_marcacion')->default(1); //(0=No, En este caso es para empleados 1= Sí, En este caso es para lapsos en los cuales la sucursal permite marcacion por el empleado)
            $table->integer('estado')->default(1);
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
