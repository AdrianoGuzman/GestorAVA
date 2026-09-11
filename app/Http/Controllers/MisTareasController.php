<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tarea\MisTareasRequest;
use App\Services\MisTareasService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * RF-09: vista única "Mis tareas", agrupada en 4 secciones segun el rol del
 * usuario respecto de cada tarea (ver MisTareasService::obtener()).
 */
class MisTareasController extends Controller
{
    public function __construct(
        private readonly MisTareasService $misTareasService,
    ) {
    }

    public function index(MisTareasRequest $request): Response
    {
        $secciones = $this->misTareasService->obtener(
            $request->user(),
            $request->validated("filtro_rol"),
        );

        return Inertia::render("mis-tareas/index", [
            "secciones" => $secciones,
        ]);
    }
}
