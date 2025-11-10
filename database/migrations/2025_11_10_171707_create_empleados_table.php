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
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user')->nullable(); // Si tendrá login en app
            $table->string('cod_trabajador', 20);
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->unsignedBigInteger('id_puesto');
            $table->unsignedBigInteger('id_depto');
            $table->unsignedBigInteger('id_sucursal');
            $table->unsignedBigInteger('id_empresa');
            $table->integer('login')->default(0); // Puede o no marcar desde dispositivo
            $table->integer('estado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
