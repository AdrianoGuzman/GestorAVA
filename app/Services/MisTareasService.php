<?php

namespace App\Services;

use App\Enums\EstadoTarea;
use App\Enums\TipoEvento;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Support\Collection;

class MisTareasService
{
    /**
     * RF-09: agrupa las tareas del usuario en 4 secciones, sin duplicar una
     * misma tarea entre ellas. "Delegadas por mí" (RF-08) solo cuenta
     * reasignaciones RF-05 explícitas, no la asignación inicial al crear
     * (RF-04 D2) -- ese caso ya lo cubre "Creadas por mí" (ver nota de
     * alcance de visibilidad del creador).
     */
    public function obtener(User $usuario, ?string $filtroRol = null): array
    {
        $responsable = $usuario->tareasComoResponsable()->get();
        $colaborador = $usuario->tareasComoColaborador()->get();

        $delegadasPorMi = Tarea::whereHas("historial", function ($query) use ($usuario) {
            $query->whereIn("tipo_evento", [TipoEvento::Reasignacion, TipoEvento::ReasignacionExcepcional])
                ->where("usuario_id", $usuario->id);
        })->get();

        $idsExcluidos = $responsable->pluck("id")
            ->merge($colaborador->pluck("id"))
            ->merge($delegadasPorMi->pluck("id"));

        $creadasPorMi = $usuario->tareasComoCreador()
            ->whereNotIn("id", $idsExcluidos)
            ->get();

        $secciones = [
            "responsable" => $this->seccion($responsable, "responsable"),
            "colaborador" => $this->seccion($colaborador, "colaborador"),
            "delegadas_por_mi" => $this->seccion($delegadasPorMi, "delegado"),
            "creadas_por_mi" => $this->seccion($creadasPorMi, "creador"),
        ];

        if ($filtroRol !== null) {
            return array_intersect_key($secciones, [$filtroRol => true]);
        }

        return $secciones;
    }

    /**
     * RF-24 D4: rol del usuario respecto de una tarea puntual, coherente con
     * las secciones (mutuamente excluyentes) de RF-09. Null si quien
     * consulta no tiene ninguna de esas relaciones directas (ej. un
     * superior de unidad que solo tiene permiso de RF-05).
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

    private function seccion(Collection $tareas, string $rol): array
    {
        return [
            "rol" => $rol,
            "contadores" => [
                "total" => $tareas->count(),
                "atrasadas" => $tareas->where("esta_atrasada", true)->count(),
                "en_progreso" => $tareas->where("estado", EstadoTarea::EnProgreso)->count(),
                "completadas" => $tareas->where("estado", EstadoTarea::Completada)->count(),
            ],
            "tareas" => $tareas->values(),
        ];
    }
}
