<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = "usuarios";

    public function up(): void
    {
        Schema::table("users", function (Blueprint $table) {
            $table->enum("nivel_jerarquico", ["directorio", "gerencia", "jefe_area", "asistente"])->nullable()->after("cargo");
            $table->foreignId("unidad_organizacional_id")->nullable()->after("nivel_jerarquico")->constrained("unidades_organizacionales")->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table("users", function (Blueprint $table) {
            $table->dropConstrainedForeignId("unidad_organizacional_id");
            $table->dropColumn("nivel_jerarquico");
        });
    }
};
