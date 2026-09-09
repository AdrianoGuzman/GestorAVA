<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = "usuarios";

    public function up(): void
    {
        Schema::create("checklist_items", function (Blueprint $table) {
            $table->id();
            $table->foreignId("tarea_id")->constrained("tareas")->cascadeOnDelete();
            $table->string("texto");
            $table->boolean("completado")->default(false);
            $table->foreignId("dueno_id")->nullable()->constrained("users")->nullOnDelete();
            $table->timestamps();

            $table->index("tarea_id");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("checklist_items");
    }
};
