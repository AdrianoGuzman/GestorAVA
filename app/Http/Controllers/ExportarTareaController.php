<?php

namespace App\Http\Controllers;

use App\Exports\TareaExport;
use App\Models\Tarea;
use App\Services\ExportacionTareaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * RF-16/24 (trazabilidad): exportar el registro completo de una tarea fuera
 * de la app -- PDF como "acta" y Excel con el mismo lenguaje visual que ya
 * usa AVA en sus planillas de seguimiento (ver CONTEXTO/Planillas excel
 * AVA/*.xlsx: encabezados en verde de marca, tablas con bordes). Sin permiso
 * propio: quien puede abrir /tareas/{tarea} (sin restriccion adicional, ver
 * TareaController::show()) puede exportar el mismo registro que esta viendo.
 */
class ExportarTareaController extends Controller
{
    public function __construct(private readonly ExportacionTareaService $exportacion)
    {
    }

    public function pdf(Request $request, Tarea $tarea): Response
    {
        $tarea = $this->cargar($tarea);

        $pdf = Pdf::loadView("pdf.tarea", [
            "tarea" => $tarea,
            "datosGenerales" => $this->exportacion->datosGenerales($tarea),
            "subtareas" => $this->exportacion->subtareas($tarea),
            "historial" => $this->exportacion->historial($tarea),
            "usuarioExportador" => $request->user()->name,
        ]);

        return $pdf->download("{$tarea->codigo}.pdf");
    }

    public function excel(Tarea $tarea): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $tarea = $this->cargar($tarea);

        return Excel::download(new TareaExport($tarea, $this->exportacion), "{$tarea->codigo}.xlsx");
    }

    private function cargar(Tarea $tarea): Tarea
    {
        return $tarea->load([
            "responsable",
            "colaboradores",
            "creador",
            "checklistItems" => fn ($query) => $query->with("dueno")->orderBy("created_at"),
            "historial" => fn ($query) => $query->with("usuario")->orderBy("created_at"),
        ]);
    }
}
