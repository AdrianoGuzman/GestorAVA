<?php

namespace Tests\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * migrate:fresh solo resetea el schema de la conexion por defecto ("usuarios"),
 * pero la tabla de control de migraciones vive en el schema "laravel" (conexion
 * separada) y nunca se toca. Sin este trait, la segunda vez que corre el suite
 * el migrator ve el historial de la corrida anterior y no vuelve a crear nada
 * en "usuarios", dejando las tablas del dominio inexistentes.
 *
 * Envuelve RefreshDatabase en vez de heredar de TestCase porque Pest aplica el
 * trait directo sobre la clase de test: un trait pisa un metodo heredado de la
 * clase padre, asi que sobreescribir beforeRefreshingDatabase() en TestCase no
 * tiene efecto.
 *
 * OJO: no dropear tambien "usuarios" aca. Se probo (ver historial de commits)
 * y dropear el schema de la conexion default activa causa que Postgres
 * pierda la durabilidad de las tablas creadas por la migracion siguiente en
 * cuanto termina la transaccion envolvente del primer test - el schema
 * queda realmente vacio para el segundo test en adelante. dropAllTables()
 * de migrate:fresh ya se encarga de "usuarios" sin este problema.
 */
trait RefreshesDualSchemaDatabase
{
    use RefreshDatabase {
        RefreshDatabase::beforeRefreshingDatabase as protected baseBeforeRefreshingDatabase;
    }

    protected function beforeRefreshingDatabase()
    {
        $this->baseBeforeRefreshingDatabase();

        DB::connection('usuarios')->statement('DROP SCHEMA IF EXISTS laravel CASCADE');
        DB::connection('usuarios')->statement('CREATE SCHEMA laravel');
    }
}
