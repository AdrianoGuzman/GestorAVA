<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = "usuarios";

    public function up(): void
    {
        Schema::create("colaboradores_tarea", function (Blueprint $table) {
            $table->id();
            $table->foreignId("tarea_id")->constrained("tareas")->cascadeOnDelete();
            $table->foreignId("usuario_id")->constrained("users")->cascadeOnDelete();
            $table->unique(["tarea_id", "usuario_id"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("colaboradores_tarea");
    }
};
