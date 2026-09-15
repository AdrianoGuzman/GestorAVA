<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Franco (14-09-2026): trazabilidad del Proyecto, igual criterio que
 * historial_tareas -- quien lo creo, que cambio en cada edicion (diff, igual
 * que "Idea A" de Tarea) y cuando se agrega/edita una Seccion.
 */
return new class extends Migration
{
    protected $connection = "usuarios";

    public function up(): void
    {
        Schema::create("historial_proyectos", function (Blueprint $table) {
            $table->id();
            $table->foreignId("proyecto_id")->constrained("proyectos")->cascadeOnDelete();
            $table->string("tipo_evento");
            $table->foreignId("usuario_id")->nullable()->constrained("users")->nullOnDelete();
            $table->jsonb("datos_evento")->nullable();
            $table->timestamp("created_at")->useCurrent();

            $table->index(["proyecto_id", "created_at"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("historial_proyectos");
    }
};
