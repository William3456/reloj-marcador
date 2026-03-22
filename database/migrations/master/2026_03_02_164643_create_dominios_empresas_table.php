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
        Schema::create('dominios_empresas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('dominio')->unique(); // Aquí guardaremos "www.tecnologiassv.org" o "empresa1.tecnologiassv.org"
            $table->string('db_database');       // Ej: "tecno2_reloj_marc_bd"
            $table->string('db_username');
            $table->string('db_password')->nullable();
            $table->string('logo')->nullable(); // Para futuras personalizaciones
            $table->json('configuraciones')->nullable(); // Para las personalizaciones futuras
            $table->integer('tipo_licencia')->default(1)->comment('1: Permanente, 0: Demo');
            $table->date('fecha_exp_licencia')->nullable()->comment('Solo para licencias demo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dominios_empresas');
    }
};
