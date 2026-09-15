<?php

namespace App\Http\Controllers;

use App\Http\Requests\Seccion\ActualizarSeccionRequest;
use App\Http\Requests\Seccion\CrearSeccionRequest;
use App\Models\Proyecto;
use App\Models\Seccion;
use App\Services\ProyectoService;
use Illuminate\Http\RedirectResponse;

/**
 * Secciones dentro de un Proyecto (agrupan tareas por objetivo, con un peso
 * para ponderar el avance del proyecto). Solo Directorio/Gerencia, ver
 * NivelJerarquico::puedeAdministrarProyectos().
 */
class SeccionController extends Controller
{
    public function __construct(private readonly ProyectoService $proyectoService)
    {
    }

    public function store(CrearSeccionRequest $request, Proyecto $proyecto): RedirectResponse
    {
        $seccion = $this->proyectoService->crearSeccion($proyecto, $request->validated(), $request->user());

        return back()->with("success", "Sección \"{$seccion->nombre}\" creada correctamente.");
    }

    public function update(ActualizarSeccionRequest $request, Seccion $seccion): RedirectResponse
    {
        $seccion = $this->proyectoService->actualizarSeccion($seccion, $request->validated(), $request->user());

        return back()->with("success", "Sección \"{$seccion->nombre}\" actualizada correctamente.");
    }
}
