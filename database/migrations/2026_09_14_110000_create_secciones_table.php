<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Franco (14-09-2026): dentro de un Proyecto, una Seccion agrupa tareas por
 * objetivo (ej. "Objetivo 1: Cultura") -- calcado del nivel "Premisa" de las
 * planillas de AVA. Cada seccion tiene un peso (0-1) para ponderar el avance
 * del proyecto completo (ver ProyectoController::avanceProyecto()).
 */
return new class extends Migration
{
    protected $connection = "usuarios";

    public function up(): void
    {
        Schema::create("secciones", function (Blueprint $table) {
            $table->id();
            $table->foreignId("proyecto_id")->constrained("proyectos")->cascadeOnDelete();
            $table->string("nombre");
            $table->decimal("peso", 4, 3)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("secciones");
    }
};
