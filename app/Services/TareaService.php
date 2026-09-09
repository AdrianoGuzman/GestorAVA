<?php

namespace App\Services;

use App\Enums\EstadoTarea;
use App\Enums\TipoEvento;
use App\Models\Tarea;
use App\Models\User;
use App\Repositories\Contracts\TareaRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TareaService
{
    public function __construct(
        private readonly TareaRepositoryInterface $tareas,
        private readonly HistorialService $historial,
        private readonly NotificacionService $notificaciones,
    ) {
    }

    /**
     * RF-04: crea una tarea, con el creador preasignado como responsable
     * salvo que se indique otro, y notifica a responsable/colaboradores
     * distintos del creador (RF-17).
     */
    public function crear(array $datos, User $creador): Tarea
    {
        $responsable = $datos["responsable_id"] === $creador->id
            ? $creador
            : User::findOrFail($datos["responsable_id"]);

        if ($responsable->unidad_organizacional_id === null) {
            throw ValidationException::withMessages([
                "responsable_id" => "El responsable no tiene una unidad organizacional asignada.",
            ]);
        }

        $colaboradorIds = collect($datos["colaboradores"] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn ($id) => $id === $responsable->id)
            ->values();

        $tarea = DB::connection("usuarios")->transaction(function () use ($datos, $creador, $responsable, $colaboradorIds) {
            $tarea = $this->tareas->crear([
                "titulo" => $datos["titulo"],
                "descripcion" => $datos["descripcion"] ?? null,
                "responsable_id" => $responsable->id,
                "creador_id" => $creador->id,
                "unidad_organizacional_id" => $responsable->unidad_organizacional_id,
                "fecha_inicio" => $datos["fecha_inicio"] ?? null,
                "fecha_compromiso" => $datos["fecha_compromiso"],
                "estado" => EstadoTarea::Pendiente,
                "esta_atrasada" => false,
            ]);

            if ($colaboradorIds->isNotEmpty()) {
                $tarea->colaboradores()->attach($colaboradorIds);
            }

            $this->historial->registrar($tarea, TipoEvento::Creacion, $creador, [
                "responsable_id" => $responsable->id,
                "colaboradores" => $colaboradorIds->all(),
                "fecha_compromiso" => $datos["fecha_compromiso"],
            ]);

            return $tarea;
        });

        if ($responsable->id !== $creador->id) {
            $this->notificaciones->notificarAsignacion($responsable, $tarea, "responsable");
        }

        foreach ($tarea->colaboradores as $colaborador) {
            if ($colaborador->id !== $creador->id) {
                $this->notificaciones->notificarAsignacion($colaborador, $tarea, "colaborador");
            }
        }

        return $tarea;
    }
}
