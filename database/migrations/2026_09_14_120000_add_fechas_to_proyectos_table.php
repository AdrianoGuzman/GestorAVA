<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Franco (14-09-2026): "los proyectos siempre tienen plazo" -- fecha_termino
 * pasa a ser obligatoria a nivel de formulario (ver CrearProyectoRequest);
 * nullable en la BD para no romper el unico proyecto de prueba que ya existe
 * sin estas fechas.
 */
return new class extends Migration
{
    protected $connection = "usuarios";

    public function up(): void
    {
        Schema::table("proyectos", function (Blueprint $table) {
            $table->date("fecha_inicio")->nullable()->after("descripcion");
            $table->date("fecha_termino")->nullable()->after("fecha_inicio");
        });
    }

    public function down(): void
    {
        Schema::table("proyectos", function (Blueprint $table) {
            $table->dropColumn(["fecha_inicio", "fecha_termino"]);
        });
    }
};
