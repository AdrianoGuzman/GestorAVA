<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tarea\MisTareasRequest;
use App\Services\MisTareasService;
use Illuminate\Http\JsonResponse;

/**
 * RF-09: vista única "Mis tareas" (endpoint minimo, responde JSON hasta que
 * RF-24 construya la vista de detalle/listado real).
 */
class MisTareasController extends Controller
{
    public function __construct(
        private readonly MisTareasService $misTareasService,
    ) {
    }

    public function index(MisTareasRequest $request): JsonResponse
    {
        $secciones = $this->misTareasService->obtener(
            $request->user(),
            $request->validated("filtro_rol"),
        );

        return response()->json($secciones);
    }
}
