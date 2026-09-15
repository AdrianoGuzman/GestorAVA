<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrupa tareas de una o varias unidades organizacionales bajo una misma
 * iniciativa estrategica (ej. "Cultura preventiva"), calcado de como las
 * planillas de control de avance de AVA agrupan actividades bajo una
 * premisa. Decision de Franco (14-09-2026).
 */
return new class extends Migration
{
    protected $connection = "usuarios";

    public function up(): void
    {
        Schema::create("proyectos", function (Blueprint $table) {
            $table->id();
            $table->string("nombre")->unique();
            $table->text("descripcion")->nullable();
            $table->foreignId("creador_id")->constrained("users");
            $table->enum("estado", ["activo", "cerrado"])->default("activo");
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("proyectos");
    }
};
