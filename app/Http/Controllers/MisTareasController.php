<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tarea\MisTareasRequest;
use App\Models\Proyecto;
use App\Models\Seccion;
use App\Models\UnidadOrganizacional;
use App\Models\User;
use App\Services\MisTareasService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * RF-09: vista única "Mis tareas" -- listado unico de tareas del usuario,
 * con busqueda, filtros y un filtro rapido por rol (ver
 * MisTareasService::obtener()).
 */
class MisTareasController extends Controller
{
    public function __construct(
        private readonly MisTareasService $misTareasService,
    ) {
    }

    public function index(MisTareasRequest $request): Response
    {
        $filtros = $request->validated();

        $resultado = $this->misTareasService->obtener($request->user(), $filtros);

        return Inertia::render("mis-tareas/index", [
            "tareas" => $resultado["tareas"],
            "contadores" => $resultado["contadores"],
            "filtros" => $resultado["filtros"],
            "unidadesOrganizacionales" => UnidadOrganizacional::orderBy("nombre")->get(["id", "nombre"]),
            "usuarios" => User::select(["id", "nombre_1", "nombre_2", "apellido_1", "apellido_2", "email"])->get(),
            "proyectos" => Proyecto::orderBy("nombre")->get(["id", "nombre", "fecha_inicio", "fecha_termino"]),
            "secciones" => Seccion::orderBy("nombre")->get(["id", "nombre", "proyecto_id"]),
        ]);
    }
}
