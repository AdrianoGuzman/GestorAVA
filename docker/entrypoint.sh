#!/bin/sh
set -e

php artisan config:clear

# En una base de datos totalmente nueva ni "usuarios" ni "laravel" existen
# como schema todavia -- pero la tabla de migraciones vive en "laravel"
# (config/database.php) y la migracion que crea ambos schemas es, a su vez,
# la primera migracion. Sin este paso, migrate falla antes de poder correr
# esa misma migracion (huevo y gallina). Idempotente: no rompe nada si ya
# existen (entornos locales los tienen desde hace tiempo).
php -r '
$pdo = new PDO(
    sprintf("pgsql:host=%s;port=%s;dbname=%s", getenv("PG_HOST"), getenv("PG_PORT"), getenv("PG_DATABASE")),
    getenv("PG_USERNAME"),
    getenv("PG_PASSWORD"),
);
$pdo->exec("CREATE SCHEMA IF NOT EXISTS usuarios");
$pdo->exec("CREATE SCHEMA IF NOT EXISTS laravel");
'

php artisan migrate --force
php artisan storage:link || true

# Solo para esta demo: si la base quedo vacia (deploy nuevo), carga los
# datos de prueba (unidades organizacionales + un usuario por nivel
# jerarquico, ver UsuarioSeeder). Los seeders no son idempotentes (usan
# create(), no firstOrCreate()) asi que esto SOLO corre si no hay ningun
# usuario todavia -- en redeploys posteriores no vuelve a duplicar nada.
if [ "$(php artisan tinker --execute='echo \App\Models\User::count();' 2>/dev/null | tail -1)" = "0" ]; then
    php artisan db:seed --force
fi

# Solo para esta demo: alguien edito por error el nivel jerarquico de
# admin@ava.cl y gerencia@ava.cl a "jefe_area" (y la unidad de asistente@ava.cl
# a "Directorio AVA") desde Administracion de usuarios -- y como "jefe_area"
# no tiene acceso a esa pantalla, nadie podia entrar a corregirlo por la UI.
# Reaplica los valores correctos del seeder en cada boot (idempotente -- no
# hace nada si ya estan bien).
php artisan tinker --execute='
\App\Models\User::where("email", "admin@ava.cl")->update(["nivel_jerarquico" => \App\Enums\NivelJerarquico::Directorio]);
\App\Models\User::where("email", "gerencia@ava.cl")->update(["nivel_jerarquico" => \App\Enums\NivelJerarquico::Gerencia]);
$areaElectrica = \App\Models\UnidadOrganizacional::where("nombre", "Área Eléctrica")->first();
if ($areaElectrica) {
    \App\Models\User::where("email", "asistente@ava.cl")->update(["unidad_organizacional_id" => $areaElectrica->id]);
}
' > /dev/null 2>&1 || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
