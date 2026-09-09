<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tarea\CrearTareaRequest;
use App\Services\TareaService;
use Illuminate\Http\RedirectResponse;

class TareaController extends Controller
{
    public function __construct(private readonly TareaService $tareaService)
    {
    }

    public function store(CrearTareaRequest $request): RedirectResponse
    {
        $tarea = $this->tareaService->crear($request->validated(), $request->user());

        return back()->with("success", "Tarea \"{$tarea->titulo}\" creada correctamente.");
    }
}
