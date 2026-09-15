<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opcional: una tarea de un proyecto puede quedar sin seccion asignada (ver
 * ContextoProgramacion.md, 14-09-2026) -- se muestra igual en un grupo
 * "Sin sección", solo que no participa del avance ponderado del proyecto.
 */
return new class extends Migration
{
    protected $connection = "usuarios";

    public function up(): void
    {
        Schema::table("tareas", function (Blueprint $table) {
            $table->foreignId("seccion_id")->nullable()->after("proyecto_id")->constrained("secciones")->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table("tareas", function (Blueprint $table) {
            $table->dropConstrainedForeignId("seccion_id");
        });
    }
};
