<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tarea\AgregarChecklistPersonalRequest;
use App\Models\ChecklistPersonalItem;
use App\Models\Tarea;
use App\Services\ChecklistPersonalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChecklistPersonalController extends Controller
{
    public function __construct(
        private readonly ChecklistPersonalService $checklistPersonalService,
    ) {
    }

    public function store(AgregarChecklistPersonalRequest $request, Tarea $tarea): RedirectResponse|JsonResponse
    {
        $this->checklistPersonalService->agregar($tarea, $request->user(), $request->validated("texto"));

        return $this->exito("Ítem agregado a tu checklist.");
    }

    public function alternar(Request $request, Tarea $tarea, ChecklistPersonalItem $item): RedirectResponse|JsonResponse
    {
        abort_unless($item->tarea_id === $tarea->id, 404);

        $this->checklistPersonalService->alternar($item, $request->user());

        return $this->exito("Ítem actualizado.");
    }

    public function destroy(Request $request, Tarea $tarea, ChecklistPersonalItem $item): RedirectResponse|JsonResponse
    {
        abort_unless($item->tarea_id === $tarea->id, 404);

        $this->checklistPersonalService->eliminar($item, $request->user());

        return $this->exito("Ítem eliminado.");
    }
}
