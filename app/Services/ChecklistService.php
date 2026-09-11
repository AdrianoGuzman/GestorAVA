<?php

namespace App\Services;

use App\Enums\TipoEvento;
use App\Exceptions\PermisoDenegadoException;
use App\Models\ChecklistItem;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChecklistService
{
    public function __construct(
        private readonly HistorialService $historial,
        private readonly PermisosService $permisos,
    ) {
    }

    public function crear(
        Tarea $tarea,
        string $texto,
        ?User $dueno,
        User $usuario,
    ): ChecklistItem {
        if (! $this->permisos->puedeUsarChecklist($tarea, $usuario)) {
            throw new PermisoDenegadoException(
                "Solo el responsable o un colaborador de la tarea puede usar el checklist."
            );
        }

        if ($dueno !== null && ! $this->permisos->puedeAsignarDuenoChecklist($tarea, $usuario)) {
            throw new PermisoDenegadoException(
                "Solo el creador de la tarea puede asignar el dueño de un ítem del checklist."
            );
        }

        $this->validarDueno($tarea, $dueno);

        return DB::connection("usuarios")->transaction(function () use ($tarea, $texto, $dueno, $usuario) {
            $item = $tarea->checklistItems()->create([
                "texto" => $texto,
                "completado" => false,
                "dueno_id" => $dueno?->id,
            ]);

            $this->historial->registrar(
                $tarea,
                TipoEvento::ChecklistItemCreado,
                $usuario,
                [
                    "checklist_item_id" => $item->id,
                    "texto" => $item->texto,
                    "dueno_id" => $item->dueno_id,
                ],
            );

            return $item;
        });
    }

    public function editar(
        ChecklistItem $item,
        string $texto,
        ?User $dueno,
        User $usuario,
    ): ChecklistItem {
        $tarea = $item->tarea;

        if (! $this->permisos->puedeUsarChecklist($tarea, $usuario)) {
            throw new PermisoDenegadoException(
                "Solo el responsable o un colaborador de la tarea puede usar el checklist."
            );
        }

        $cambiaDueno = $dueno?->id !== $item->dueno_id;

        if ($cambiaDueno && ! $this->permisos->puedeAsignarDuenoChecklist($tarea, $usuario)) {
            throw new PermisoDenegadoException(
                "Solo el creador de la tarea puede asignar el dueño de un ítem del checklist."
            );
        }

        $this->validarDueno($tarea, $dueno);

        return DB::connection("usuarios")->transaction(function () use ($item, $texto, $dueno, $usuario, $tarea) {
            $datosAnteriores = [
                "texto" => $item->texto,
                "dueno_id" => $item->dueno_id,
            ];

            $item->update([
                "texto" => $texto,
                "dueno_id" => $dueno?->id,
            ]);

            $this->historial->registrar(
                $tarea,
                TipoEvento::ChecklistItemEditado,
                $usuario,
                [
                    "checklist_item_id" => $item->id,
                    "datos_anteriores" => $datosAnteriores,
                    "texto" => $item->texto,
                    "dueno_id" => $item->dueno_id,
                ],
            );

            return $item->fresh();
        });
    }

    public function marcar(ChecklistItem $item, User $usuario): ChecklistItem
    {
        return $this->cambiarCompletado($item, true, $usuario);
    }

    public function desmarcar(ChecklistItem $item, User $usuario): ChecklistItem
    {
        return $this->cambiarCompletado($item, false, $usuario);
    }

    private function cambiarCompletado(
        ChecklistItem $item,
        bool $completado,
        User $usuario,
    ): ChecklistItem {
        if (! $this->permisos->puedeMarcarChecklistItem($item, $usuario)) {
            throw new PermisoDenegadoException(
                "Solo el dueño asignado de este ítem puede marcarlo o desmarcarlo."
            );
        }

        return DB::connection("usuarios")->transaction(function () use ($item, $completado, $usuario) {
            $item->update([
                "completado" => $completado,
            ]);

            $this->historial->registrar(
                $item->tarea,
                $completado
                    ? TipoEvento::ChecklistItemMarcado
                    : TipoEvento::ChecklistItemDesmarcado,
                $usuario,
                [
                    "checklist_item_id" => $item->id,
                ],
            );

            return $item->fresh();
        });
    }

    public function eliminar(ChecklistItem $item, User $usuario): void
    {
        if (! $this->permisos->puedeUsarChecklist($item->tarea, $usuario)) {
            throw new PermisoDenegadoException(
                "Solo el responsable o un colaborador de la tarea puede usar el checklist."
            );
        }

        DB::connection("usuarios")->transaction(function () use ($item, $usuario) {
            $tarea = $item->tarea;
            $itemId = $item->id;

            $item->delete();

            $this->historial->registrar(
                $tarea,
                TipoEvento::ChecklistItemEliminado,
                $usuario,
                [
                    "checklist_item_id" => $itemId,
                ],
            );
        });
    }

    private function validarDueno(Tarea $tarea, ?User $dueno): void
    {
        if ($dueno === null) {
            return;
        }

        if ($dueno->id === $tarea->responsable_id) {
            return;
        }

        if ($tarea->colaboradores()->whereKey($dueno->id)->exists()) {
            return;
        }

        throw new PermisoDenegadoException(
            "El dueño del checklist debe ser el responsable o un colaborador de la tarea."
        );
    }
}
