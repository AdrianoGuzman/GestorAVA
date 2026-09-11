<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = "usuarios";

    public function up(): void
    {
        Schema::create("checklist_personal_items", function (Blueprint $table) {
            $table->id();
            $table->foreignId("tarea_id")->constrained("tareas")->cascadeOnDelete();
            $table->foreignId("usuario_id")->constrained("users")->cascadeOnDelete();
            $table->string("texto");
            $table->boolean("completado")->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("checklist_personal_items");
    }
};
