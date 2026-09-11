<?php

namespace App\Http\Controllers;

use App\Http\Requests\Checklist\CrearChecklistItemRequest;
use App\Http\Requests\Checklist\EditarChecklistItemRequest;
use App\Models\ChecklistItem;
use App\Models\Tarea;
use App\Models\User;
use App\Services\ChecklistService;
use Illuminate\Http\RedirectResponse;

class ChecklistController extends Controller
{
    public function __construct(
        private readonly ChecklistService $checklistService,
    ) {
    }

    public function store(CrearChecklistItemRequest $request, Tarea $tarea): RedirectResponse
    {
        $datos = $request->validated();

        $this->checklistService->crear(
            $tarea,
            $datos["texto"],
            isset($datos["dueno_id"]) ? User::findOrFail($datos["dueno_id"]) : null,
            $request->user(),
        );

        return back()->with("success", "Ítem de checklist creado correctamente.");
    }

    public function update(EditarChecklistItemRequest $request, ChecklistItem $checklistItem): RedirectResponse
    {
        $datos = $request->validated();

        $this->checklistService->editar(
            $checklistItem,
            $datos["texto"],
            isset($datos["dueno_id"]) ? User::findOrFail($datos["dueno_id"]) : null,
            $request->user(),
        );

        return back()->with("success", "Ítem de checklist actualizado correctamente.");
    }

    public function marcar(ChecklistItem $checklistItem): RedirectResponse
    {
        $this->checklistService->marcar($checklistItem, request()->user());

        return back()->with("success", "Ítem de checklist marcado correctamente.");
    }

    public function desmarcar(ChecklistItem $checklistItem): RedirectResponse
    {
        $this->checklistService->desmarcar($checklistItem, request()->user());

        return back()->with("success", "Ítem de checklist desmarcado correctamente.");
    }

    public function destroy(ChecklistItem $checklistItem): RedirectResponse
    {
        $this->checklistService->eliminar($checklistItem, request()->user());

        return back()->with("success", "Ítem de checklist eliminado correctamente.");
    }
}


