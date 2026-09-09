<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = "usuarios";

    public function up(): void
    {
        Schema::create("historial_tareas", function (Blueprint $table) {
            $table->id();
            $table->foreignId("tarea_id")->constrained("tareas")->cascadeOnDelete();
            $table->string("tipo_evento");
            $table->foreignId("usuario_id")->nullable()->constrained("users")->nullOnDelete();
            $table->jsonb("datos_evento")->nullable();
            $table->timestamp("created_at")->useCurrent();

            $table->index(["tarea_id", "created_at"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("historial_tareas");
    }
};
