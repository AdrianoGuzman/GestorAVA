<?php

namespace App\Services;

use App\Enums\TipoEvento;
use App\Exceptions\PermisoDenegadoException;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ColaboradorService
{
    public function __construct(
        private readonly PermisosService $permisos,
        private readonly HistorialService $historial,
        private readonly NotificacionService $notificaciones,
    ) {
    }

    /**
     * RF-06: agrega uno o mas colaboradores a una tarea sin transferir la
     * responsabilidad principal. Ignora silenciosamente los ids que ya son
     * colaboradores o que coinciden con el responsable actual.
     */
    public function agregar(Tarea $tarea, array $idsColaboradores, User $solicitante): Tarea
    {
        if (! $this->permisos->puedeAgregarColaborador($tarea, $solicitante)) {
            throw new PermisoDenegadoException(
                $tarea->estado->esTerminal()
                    ? "No se pueden agregar colaboradores a una tarea completada o cancelada."
                    : "Solo quien creó la tarea o su responsable actual pueden agregar colaboradores."
            );
        }

        $colaboradoresActuales = $tarea->colaboradores->pluck("id");

        $idsNuevos = collect($idsColaboradores)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn ($id) => $id === $tarea->responsable_id)
            ->reject(fn ($id) => $colaboradoresActuales->contains($id))
            ->values();

        if ($idsNuevos->isEmpty()) {
            return $tarea;
        }

        DB::connection("usuarios")->transaction(function () use ($tarea, $idsNuevos, $solicitante) {
            $tarea->colaboradores()->attach($idsNuevos);

            foreach ($idsNuevos as $id) {
                $this->historial->registrar($tarea, TipoEvento::ColaboradorAgregado, $solicitante, [
                    "colaborador_id" => $id,
                ]);
            }
        });

        $tarea = $tarea->fresh();

        foreach ($tarea->colaboradores->whereIn("id", $idsNuevos->all()) as $colaborador) {
            if ($colaborador->id !== $solicitante->id) {
                $this->notificaciones->notificarAsignacion($colaborador, $tarea, "colaborador");
            }
        }

        return $tarea;
    }
}
