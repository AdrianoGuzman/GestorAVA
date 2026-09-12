<?php

namespace App\Services;

use App\Enums\EstadoTarea;
use App\Enums\TipoEvento;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

class MisTareasService
{
    /**
     * RF-09: agrupa las tareas del usuario en 4 roles, sin duplicar una misma
     * tarea entre ellos, y las devuelve como una lista unica (cada tarea
     * marcada con el rol del que vino) para la vista de listado. "Delegadas
     * por mí" (RF-08) solo cuenta reasignaciones RF-05 explícitas, no la
     * asignación inicial al crear (RF-04 D2) -- ese caso ya lo cubre
     * "Creadas por mí".
     *
     * $filtros acepta: busqueda (string), estado (string[]), solo_atrasadas
     * (bool), unidad_organizacional_id (int), filtro_rol (string, limita el
     * resultado a un solo rol -- usado por el filtro rapido del frontend).
     */
    public function obtener(User $usuario, array $filtros = []): array
    {
        $filtroRol = $filtros["filtro_rol"] ?? null;

        // Los ids excluidos de "creadas por mi" se calculan SIN los filtros
        // de busqueda/estado/etc, porque el rol de una tarea no depende de
        // si queda visible bajo el filtro actual.
        $idsExcluidos = $usuario->tareasComoResponsable()->pluck("tareas.id")
            ->merge($usuario->tareasComoColaborador()->pluck("tareas.id"))
            ->merge($this->consultaDelegadasPorMi($usuario)->pluck("tareas.id"));

        $roles = [
            "responsable" => fn () => $this->aplicarFiltros($usuario->tareasComoResponsable(), $filtros),
            "colaborador" => fn () => $this->aplicarFiltros($usuario->tareasComoColaborador(), $filtros),
            "delegado" => fn () => $this->aplicarFiltros($this->consultaDelegadasPorMi($usuario), $filtros),
            "creador" => fn () => $this->aplicarFiltros(
                $usuario->tareasComoCreador()->whereNotIn("tareas.id", $idsExcluidos),
                $filtros,
            ),
        ];

        $seccionPorFiltro = [
            "responsable" => "responsable",
            "colaborador" => "colaborador",
            "delegadas_por_mi" => "delegado",
            "creadas_por_mi" => "creador",
        ];

        if ($filtroRol !== null) {
            $roles = array_intersect_key($roles, [$seccionPorFiltro[$filtroRol] => true]);
        }

        $tareas = collect($roles)
            ->flatMap(function ($consulta, $rol) {
                return $consulta()->with(["responsable", "unidadOrganizacional"])->get()
                    ->each(fn (Tarea $tarea) => $tarea->rol = $rol);
            })
            ->sortBy("fecha_compromiso")
            ->values();

        return [
            "tareas" => $tareas,
            "contadores" => [
                "total" => $tareas->count(),
                "atrasadas" => $tareas->where("esta_atrasada", true)->count(),
                "en_progreso" => $tareas->where("estado", EstadoTarea::EnProgreso)->count(),
                "pendientes" => $tareas->where("estado", EstadoTarea::Pendiente)->count(),
                "completadas" => $tareas->where("estado", EstadoTarea::Completada)->count(),
            ],
        ];
    }

    /**
     * RF-24 D4: rol del usuario respecto de una tarea puntual, coherente con
     * los roles (mutuamente excluyentes) de RF-09. Null si quien consulta no
     * tiene ninguna de esas relaciones directas (ej. un superior de unidad
     * que solo tiene permiso de RF-05).
     */
    public function rolDe(Tarea $tarea, User $usuario): ?string
    {
        if ($usuario->id === $tarea->responsable_id) {
            return "responsable";
        }

        if ($tarea->colaboradores->contains("id", $usuario->id)) {
            return "colaborador";
        }

        $delego = $tarea->historial
            ->whereIn("tipo_evento", [TipoEvento::Reasignacion, TipoEvento::ReasignacionExcepcional])
            ->where("usuario_id", $usuario->id)
            ->isNotEmpty();

        if ($delego) {
            return "delegado";
        }

        if ($usuario->id === $tarea->creador_id) {
            return "creador";
        }

        return null;
    }

    private function consultaDelegadasPorMi(User $usuario): Builder
    {
        return Tarea::whereHas("historial", function ($query) use ($usuario) {
            $query->whereIn("tipo_evento", [TipoEvento::Reasignacion, TipoEvento::ReasignacionExcepcional])
                ->where("usuario_id", $usuario->id);
        });
    }

    /**
     * Las columnas se califican con "tareas." porque tareasComoColaborador()
     * es un BelongsToMany contra colaboradores_tarea, que tiene su propio
     * "id" -- sin calificar, Postgres rechaza la consulta por ambigua.
     *
     * @param  Builder|Relation  $query
     */
    private function aplicarFiltros($query, array $filtros)
    {
        if (! empty($filtros["busqueda"])) {
            $busqueda = $filtros["busqueda"];
            $idPorCodigo = Tarea::idDesdeCodigo($busqueda);

            $query->where(function ($sub) use ($busqueda, $idPorCodigo) {
                $sub->where("tareas.titulo", "ilike", "%{$busqueda}%");
                if ($idPorCodigo !== null) {
                    $sub->orWhere("tareas.id", $idPorCodigo);
                }
            });
        }

        if (! empty($filtros["estado"])) {
            $query->whereIn("tareas.estado", $filtros["estado"]);
        }

        if (! empty($filtros["solo_atrasadas"])) {
            $query->where("tareas.esta_atrasada", true);
        }

        if (! empty($filtros["unidad_organizacional_id"])) {
            $query->where("tareas.unidad_organizacional_id", $filtros["unidad_organizacional_id"]);
        }

        return $query;
    }
}
