<?php

namespace App\Services;

use App\Exceptions\PermisoDenegadoException;
use App\Models\ChecklistPersonalItem;
use App\Models\Tarea;
use App\Models\User;

/**
 * "Mi checklist": guia personal y privada por tarea. Sin dueño que asignar
 * (siempre es quien la crea), no bloquea nada, no aparece en el historial
 * de la tarea -- es irrelevante para el resto de los involucrados.
 */
class ChecklistPersonalService
{
    public function __construct(
        private readonly PermisosService $permisos,
    ) {
    }

    public function agregar(Tarea $tarea, User $usuario, string $texto): ChecklistPersonalItem
    {
        if (! $this->permisos->puedeUsarChecklistPersonal($tarea, $usuario)) {
            throw new PermisoDenegadoException(
                $tarea->estado->esTerminal()
                    ? "No se pueden agregar ítems a una tarea completada o cancelada."
                    : "Solo el responsable o un colaborador de la tarea puede usar el checklist personal."
            );
        }

        return ChecklistPersonalItem::create([
            "tarea_id" => $tarea->id,
            "usuario_id" => $usuario->id,
            "texto" => $texto,
        ]);
    }

    public function alternar(ChecklistPersonalItem $item, User $usuario): ChecklistPersonalItem
    {
        $this->verificarPropietario($item, $usuario);
        $this->verificarNoTerminal($item);

        $item->update(["completado" => ! $item->completado]);

        return $item->fresh();
    }

    public function eliminar(ChecklistPersonalItem $item, User $usuario): void
    {
        $this->verificarPropietario($item, $usuario);
        $this->verificarNoTerminal($item);

        $item->delete();
    }

    private function verificarPropietario(ChecklistPersonalItem $item, User $usuario): void
    {
        if ($item->usuario_id !== $usuario->id) {
            throw new PermisoDenegadoException("Este ítem de tu checklist personal no te pertenece.");
        }
    }

    /**
     * A diferencia de agregar() (que pasa por puedeUsarChecklistPersonal),
     * marcar/eliminar un item ya existente solo depende de ser su dueño --
     * el estado terminal se valida aparte aca (13-09-2026) para que ni el
     * dueño pueda seguir tocando su checklist personal en una tarea cerrada.
     */
    private function verificarNoTerminal(ChecklistPersonalItem $item): void
    {
        if ($item->tarea->estado->esTerminal()) {
            throw new PermisoDenegadoException(
                "No se puede modificar el checklist personal de una tarea completada o cancelada."
            );
        }
    }
}
