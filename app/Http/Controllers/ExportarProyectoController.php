<?php

namespace App\Http\Controllers;

use App\Exports\ProyectoExport;
use App\Models\Proyecto;
use App\Services\ExportacionProyectoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trazabilidad del Proyecto fuera de la app -- mismo criterio que
 * ExportarTareaController (PDF como "acta", Excel con el mismo lenguaje
 * visual de las planillas de AVA). Sin permiso propio: quien puede abrir el
 * detalle del proyecto (visible para cualquier usuario, ver
 * ProyectoController::show()) puede exportar el mismo registro que esta viendo.
 */
class ExportarProyectoController extends Controller
{
    public function __construct(private readonly ExportacionProyectoService $exportacion)
    {
    }

    public function pdf(Request $request, Proyecto $proyecto): Response
    {
        $proyecto = $this->cargar($proyecto);

        $pdf = Pdf::loadView("pdf.proyecto", [
            "proyecto" => $proyecto,
            "datosGenerales" => $this->exportacion->datosGenerales($proyecto),
            "secciones" => $this->exportacion->secciones($proyecto),
            "tareas" => $this->exportacion->tareas($proyecto),
            "historial" => $this->exportacion->historial($proyecto),
            "usuarioExportador" => $request->user()->name,
        ]);

        return $pdf->download("{$this->nombreArchivo($proyecto)}.pdf");
    }

    public function excel(Proyecto $proyecto): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $proyecto = $this->cargar($proyecto);

        return Excel::download(new ProyectoExport($proyecto, $this->exportacion), "{$this->nombreArchivo($proyecto)}.xlsx");
    }

    /** El nombre del proyecto es texto libre (a diferencia del código de una tarea) -- se limpia para que sirva como nombre de archivo. */
    private function nombreArchivo(Proyecto $proyecto): string
    {
        return trim(preg_replace('/[^\p{L}\p{N} _-]+/u', '', $proyecto->nombre)) ?: "proyecto-{$proyecto->id}";
    }

    private function cargar(Proyecto $proyecto): Proyecto
    {
        return $proyecto->load([
            "creador",
            "secciones.tareas.responsable",
            "tareas" => fn ($query) => $query->with("responsable")->whereNull("seccion_id"),
            "historial" => fn ($query) => $query->with("usuario")->orderBy("created_at"),
        ]);
    }
}
