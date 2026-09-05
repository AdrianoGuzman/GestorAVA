<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = "usuarios";

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("roles", function (Blueprint $table) {
            $table->id();
            $table->text("nombre");
        });

        Schema::create("permisos", function (Blueprint $table) {
            $table->id();
            $table->text("nombre");
        });

        Schema::create("roles_tienen_permisos", function (Blueprint $table) {
            $table->id();
            $table->foreignId("id_rol")->constrained("roles");
            $table->foreignId("id_permiso")->constrained("permisos");

            $table->unique(["id_rol", "id_permiso"]);
        });

        Schema::create("usuarios_tienen_roles", function (Blueprint $table) {
            $table->id();
            $table->foreignId("id_usuario")->constrained("users");
            $table->foreignId("id_rol")->constrained("roles");
            $table->unique(["id_usuario", "id_rol"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("usuarios_tienen_roles");
        Schema::dropIfExists("roles_tienen_permisos");
        Schema::dropIfExists("permisos");
        Schema::dropIfExists("roles");
    }
};
