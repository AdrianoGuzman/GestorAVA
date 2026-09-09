<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = "usuarios";

    public function up(): void
    {
        Schema::create("notificaciones", function (Blueprint $table) {
            $table->id();
            $table->foreignId("usuario_id")->constrained("users")->cascadeOnDelete();
            $table->foreignId("tarea_id")->constrained("tareas")->cascadeOnDelete();
            $table->string("tipo");
            $table->text("mensaje")->nullable();
            $table->boolean("leida")->default(false);
            $table->timestamp("created_at")->useCurrent();

            $table->index(["usuario_id", "leida"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("notificaciones");
    }
};
