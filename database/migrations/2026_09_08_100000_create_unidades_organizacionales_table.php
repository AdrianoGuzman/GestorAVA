<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = "usuarios";

    public function up(): void
    {
        Schema::create("unidades_organizacionales", function (Blueprint $table) {
            $table->id();
            $table->string("nombre");
            $table->enum("tipo", ["directorio", "gerencia", "obra", "area"]);
            $table->foreignId("unidad_padre_id")->nullable()->constrained("unidades_organizacionales")->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("unidades_organizacionales");
    }
};
