<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = "usuarios";

    public function up(): void
    {
        Schema::table("tareas", function (Blueprint $table) {
            $table->boolean("recordatorio_vencimiento_enviado")->default(false)->after("esta_atrasada");
        });
    }

    public function down(): void
    {
        Schema::table("tareas", function (Blueprint $table) {
            $table->dropColumn("recordatorio_vencimiento_enviado");
        });
    }
};
