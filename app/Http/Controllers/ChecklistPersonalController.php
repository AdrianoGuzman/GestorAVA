<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tarea\AgregarChecklistPersonalRequest;
use App\Models\ChecklistPersonalItem;
use App\Models\Tarea;
use App\Services\ChecklistPersonalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChecklistPersonalController extends Controller
{
    public function __construct(
        private readonly ChecklistPersonalService $checklistPersonalService,
    ) {
    }

    public function store(AgregarChecklistPersonalRequest $request, Tarea $tarea): RedirectResponse
    {
        $this->checklistPersonalService->agregar($tarea, $request->user(), $request->validated("texto"));

        return back()->with("success", "Ítem agregado a tu checklist.");
    }

    public function alternar(Request $request, Tarea $tarea, ChecklistPersonalItem $item): RedirectResponse
    {
        abort_unless($item->tarea_id === $tarea->id, 404);

        $this->checklistPersonalService->alternar($item, $request->user());

        return back();
    }

    public function destroy(Request $request, Tarea $tarea, ChecklistPersonalItem $item): RedirectResponse
    {
        abort_unless($item->tarea_id === $tarea->id, 404);

        $this->checklistPersonalService->eliminar($item, $request->user());

        return back()->with("success", "Ítem eliminado.");
    }
}
