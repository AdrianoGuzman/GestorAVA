<?php

namespace App\Services;

use App\Enums\EstadoTarea;
use App\Enums\TipoEvento;
use App\Exceptions\PermisoDenegadoException;
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
        private readonly PermisosService $permisos,
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

    /**
     * Edita titulo/descripcion/fechas de una tarea ya creada -- antes de
     * esto no habia forma de corregir un error de tipeo o ajustar una fecha
     * sin cancelar y crear de nuevo. No toca responsable/colaboradores (eso
     * ya tiene su propio flujo, RF-05/RF-06). Bloqueada en tareas terminales
     * (completada/cancelada): no tiene sentido editar algo que ya cerro.
     */
    public function actualizar(Tarea $tarea, array $datos, User $usuario): Tarea
    {
        if (! $this->permisos->puedeEditar($tarea, $usuario)) {
            throw new PermisoDenegadoException(
                "Solo el responsable o quien creó la tarea puede editarla."
            );
        }

        if (in_array($tarea->estado, [EstadoTarea::Completada, EstadoTarea::Cancelada], true)) {
            throw ValidationException::withMessages([
                "titulo" => "No se puede editar una tarea completada o cancelada.",
            ]);
        }

        return DB::connection("usuarios")->transaction(function () use ($tarea, $datos, $usuario) {
            $datosAnteriores = [
                "titulo" => $tarea->titulo,
                "descripcion" => $tarea->descripcion,
                "fecha_inicio" => $tarea->fecha_inicio?->toDateString(),
                "fecha_compromiso" => $tarea->fecha_compromiso->toDateString(),
            ];

            $tarea->update([
                "titulo" => $datos["titulo"],
                "descripcion" => $datos["descripcion"] ?? null,
                "fecha_inicio" => $datos["fecha_inicio"] ?? null,
                "fecha_compromiso" => $datos["fecha_compromiso"],
            ]);

            // RF-14: si la fecha corregida ya no esta vencida, la tarea deja
            // de estar atrasada -- el indicador debe reflejar la realidad
            // actual, no quedar pegado a una fecha que ya no es la vigente.
            if ($tarea->esta_atrasada && $tarea->fecha_compromiso->greaterThanOrEqualTo(today())) {
                $tarea->update(["esta_atrasada" => false]);
            }

            $this->historial->registrar($tarea, TipoEvento::TareaEditada, $usuario, [
                "datos_anteriores" => $datosAnteriores,
                "titulo" => $tarea->titulo,
                "descripcion" => $tarea->descripcion,
                "fecha_inicio" => $tarea->fecha_inicio?->toDateString(),
                "fecha_compromiso" => $tarea->fecha_compromiso->toDateString(),
            ]);

            return $tarea->fresh();
        });
    }
}
