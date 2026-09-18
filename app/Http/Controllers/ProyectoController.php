<?php

namespace App\Http\Controllers;

use App\Http\Requests\Proyecto\ActualizarProyectoRequest;
use App\Http\Requests\Proyecto\AplazarEntregaRequest;
use App\Http\Requests\Proyecto\CrearProyectoRequest;
use App\Models\Proyecto;
use App\Models\Seccion;
use App\Models\User;
use App\Services\AvanceProyectoService;
use App\Services\ProyectoService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Mis proyectos": agrupa tareas de una o varias unidades organizacionales
 * bajo una misma iniciativa estrategica (ej. "Cultura preventiva"), calcado
 * de como las planillas de control de avance de AVA agrupan actividades bajo
 * una premisa. Visible para cualquier usuario (como "Mis tareas"); solo
 * Directorio/Gerencia pueden crear/editar/cerrar uno o administrar sus
 * secciones, ver NivelJerarquico::puedeAdministrarProyectos().
 *
 * Franco (14-09-2026): separado en listado (index, info basica) + detalle
 * (show, secciones/tareas completas) -- con varias secciones, mostrar todo
 * en la tarjeta del listado lo hacia ocupar toda la pantalla.
 */
class ProyectoController extends Controller
{
    public function __construct(
        private readonly ProyectoService $proyectoService,
        private readonly AvanceProyectoService $avance,
    ) {
    }

    public function index(): Response
    {
        $proyectos = Proyecto::with(["creador", "secciones.tareas", "tareas" => fn ($query) => $query->whereNull("seccion_id")])
            ->orderBy("nombre")
            ->get()
            ->map(fn (Proyecto $proyecto) => [
                "id" => $proyecto->id,
                "nombre" => $proyecto->nombre,
                "descripcion" => $proyecto->descripcion,
                "estado" => $proyecto->estado,
                "creador" => $proyecto->creador,
                "fecha_inicio" => $proyecto->fecha_inicio?->toDateString(),
                "fecha_termino" => $proyecto->fecha_termino?->toDateString(),
                "avance" => $this->avance->avanceProyecto($proyecto),
            ]);

        return Inertia::render("proyectos/index", [
            "proyectos" => $proyectos,
        ]);
    }

    public function show(Proyecto $proyecto): Response
    {
        $proyecto->load([
            "creador",
            "secciones.tareas.responsable",
            "tareas" => fn ($query) => $query->with("responsable")->whereNull("seccion_id"),
            "historial" => fn ($query) => $query->with("usuario")->orderBy("created_at"),
        ]);

        return Inertia::render("proyectos/show", [
            "proyecto" => [
                "id" => $proyecto->id,
                "nombre" => $proyecto->nombre,
                "descripcion" => $proyecto->descripcion,
                "estado" => $proyecto->estado,
                "creador" => $proyecto->creador,
                "fecha_inicio" => $proyecto->fecha_inicio?->toDateString(),
                "fecha_termino" => $proyecto->fecha_termino?->toDateString(),
                "secciones" => $proyecto->secciones->map(fn (Seccion $seccion) => [
                    "id" => $seccion->id,
                    "nombre" => $seccion->nombre,
                    "peso" => $seccion->peso,
                    "tareas" => $seccion->tareas,
                    "avance" => $this->avance->avanceSimple($seccion->tareas),
                ]),
                "tareasSinSeccion" => $proyecto->tareas,
                "avance" => $this->avance->avanceProyecto($proyecto),
                "contadores" => $this->avance->contadoresDeTareas(
                    $proyecto->secciones->flatMap->tareas->merge($proyecto->tareas)
                ),
                "historial" => $proyecto->historial,
            ],
            "usuarios" => User::select(["id", "nombre_1", "nombre_2", "apellido_1", "apellido_2", "email"])->get(),
        ]);
    }

    public function store(CrearProyectoRequest $request): RedirectResponse
    {
        $proyecto = $this->proyectoService->crear($request->validated(), $request->user());

        return back()->with("success", "Proyecto \"{$proyecto->nombre}\" creado correctamente.");
    }

    public function update(ActualizarProyectoRequest $request, Proyecto $proyecto): RedirectResponse
    {
        $proyecto = $this->proyectoService->actualizar($proyecto, $request->validated(), $request->user());

        return back()->with("success", "Proyecto \"{$proyecto->nombre}\" actualizado correctamente.");
    }

    public function aplazarEntrega(AplazarEntregaRequest $request, Proyecto $proyecto): RedirectResponse
    {
        $proyecto = $this->proyectoService->aplazarEntrega($proyecto, $request->validated(), $request->user());

        return back()->with("success", "Se aplazó la entrega de \"{$proyecto->nombre}\".");
    }
}
