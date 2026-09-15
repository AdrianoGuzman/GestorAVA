<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Una tarea pertenece a lo mas a un proyecto (igual que una actividad cuelga
 * de una sola premisa en las planillas de AVA) -- nullable porque la mayoria
 * de las tareas del dia a dia no necesitan estar agrupadas bajo nada.
 */
return new class extends Migration
{
    protected $connection = "usuarios";

    public function up(): void
    {
        Schema::table("tareas", function (Blueprint $table) {
            $table->foreignId("proyecto_id")->nullable()->after("unidad_organizacional_id")->constrained("proyectos")->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table("tareas", function (Blueprint $table) {
            $table->dropConstrainedForeignId("proyecto_id");
        });
    }
};
