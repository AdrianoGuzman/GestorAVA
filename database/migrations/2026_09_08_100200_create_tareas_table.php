<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = "usuarios";

    public function up(): void
    {
        Schema::create("tareas", function (Blueprint $table) {
            $table->id();
            $table->string("titulo");
            $table->text("descripcion")->nullable();

            $table->foreignId("responsable_id")->constrained("users");
            $table->foreignId("creador_id")->constrained("users");
            $table->foreignId("unidad_organizacional_id")->constrained("unidades_organizacionales");

            $table->date("fecha_inicio")->nullable();
            $table->date("fecha_compromiso");

            $table->enum("estado", ["pendiente", "en_progreso", "completada", "rechazada", "cancelada"])->default("pendiente");
            $table->boolean("esta_atrasada")->default(false);

            $table->foreignId("tarea_padre_id")->nullable()->constrained("tareas")->nullOnDelete();

            $table->text("motivo_cancelacion")->nullable();
            $table->timestamp("fecha_cancelacion")->nullable();

            $table->timestamps();

            $table->index("estado");
            $table->index("fecha_compromiso");
            $table->index("esta_atrasada");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("tareas");
    }
};
