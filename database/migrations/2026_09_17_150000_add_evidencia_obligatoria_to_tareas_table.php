<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AVA Montajes (17-09-2026): si la tarea requiere evidencia obligatoria,
 * bloquea completarla hasta que alguien suba un adjunto categoria
 * "necesario" -- ver App\Guards\EvidenciaObligatoriaGuard.
 */
return new class extends Migration
{
    protected $connection = "usuarios";

    public function up(): void
    {
        Schema::table("tareas", function (Blueprint $table) {
            $table->boolean("evidencia_obligatoria")->default(false)->after("prioridad");
        });
    }

    public function down(): void
    {
        Schema::table("tareas", function (Blueprint $table) {
            $table->dropColumn("evidencia_obligatoria");
        });
    }
};
