<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Limpia el scaffolding de "proyectos" heredado de otro sistema de AVA
 * (centro de costos) que nunca se conecto a Tarea ni se uso en ninguna
 * pantalla (0 filas en produccion). Decision de Franco (14-09-2026): partir
 * de cero con un concepto de "Proyecto" propio en vez de reutilizar esa
 * tabla -- ver la migracion que la recrea justo despues de esta.
 */
return new class extends Migration
{
    protected $connection = "usuarios";

    public function up(): void
    {
        Schema::dropIfExists("usuarios_tienen_proyectos");
        Schema::dropIfExists("proyectos");
    }

    public function down(): void
    {
        // Irreversible a proposito: la tabla vieja no tenia datos ni uso
        // real, no tiene sentido reconstruirla al revertir.
    }
};
