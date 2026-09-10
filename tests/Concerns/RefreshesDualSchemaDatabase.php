<?php

namespace Tests\Concerns;

use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * Se probaron y descartaron varias variantes de RefreshDatabase (que corre
 * migrate:fresh dentro del mismo proceso PHP que los tests) para el setup de
 * doble schema/conexion ("usuarios"/"laravel") de este proyecto: dropear
 * "laravel" antes de migrar, dropear tambien "usuarios", truncar solo la
 * tabla migrations, usar una conexion PDO nueva y separada, incluso una
 * limpieza via un proceso de sistema operativo aparte (psql por shell_exec).
 * Todas fallan de alguna forma en cuanto la base de "testing" ya tenia
 * tablas de una corrida anterior en el mismo proceso PHP - hay algo a nivel
 * de PHP/libpq (no de Laravel) con dropAllTables() sobre la conexion default
 * justo despues de tocar el otro schema en el mismo proceso.
 *
 * La solucion real: no migrar dentro del proceso de test en absoluto. La
 * migracion de la base "testing" corre como paso aparte y siempre confiable
 * (composer run test, ver composer.json) via un proceso de artisan
 * genuinamente distinto - eso nunca fallo en ninguna prueba. Los tests solo
 * envuelven cada uno en una transaccion (DatabaseTransactions puro, sin
 * gestion de migraciones), asumiendo que el schema ya existe.
 *
 * Mantiene el mismo nombre de trait que antes para no tener que tocar los
 * tests existentes que ya hacen "use RefreshesDualSchemaDatabase;".
 */
trait RefreshesDualSchemaDatabase
{
    use DatabaseTransactions;
}
