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
            $table->string("prioridad")->default("media")->after("estado");
        });
    }

    public function down(): void
    {
        Schema::table("tareas", function (Blueprint $table) {
            $table->dropColumn("prioridad");
        });
    }
};
