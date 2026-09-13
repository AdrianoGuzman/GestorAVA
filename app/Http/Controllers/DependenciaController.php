<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tarea\CrearTareaRequest;
use App\Models\Tarea;
use App\Services\DependenciaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class DependenciaController extends Controller
{
    public function __construct(
        private readonly DependenciaService $dependenciaService,
    ) {
    }

    /** RF-21: crea una tarea hija de $tarea (misma validacion que crear una tarea normal). */
    public function store(CrearTareaRequest $request, Tarea $tarea): RedirectResponse|JsonResponse
    {
        $hija = $this->dependenciaService->crearTareaHija($tarea, $request->validated(), $request->user());

        return $this->exito("Tarea hija \"{$hija->titulo}\" creada correctamente.");
    }
}
