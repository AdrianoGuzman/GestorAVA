<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = "usuarios";

    public function up(): void
    {
        Schema::create("adjuntos_tarea", function (Blueprint $table) {
            $table->id();
            $table->foreignId("tarea_id")->constrained("tareas")->cascadeOnDelete();
            $table->foreignId("usuario_id")->constrained("users")->cascadeOnDelete();
            $table->string("nombre_original");
            $table->string("ruta");
            $table->string("mime_type");
            $table->unsignedBigInteger("tamano_bytes");
            $table->timestamp("created_at")->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("adjuntos_tarea");
    }
};
