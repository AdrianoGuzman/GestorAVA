<?php

namespace App\Exports;

use App\Models\Tarea;
use App\Services\ExportacionTareaService;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Una sola hoja con las 3 tablas apiladas (datos generales, subtareas,
 * historial) -- decision de Franco, mas simple de abrir e imprimir de un
 * vistazo que repartirlas en pestañas separadas. FromView deja escribir la
 * hoja como una tabla HTML normal (mismo Blade que se veria en un navegador)
 * y Maatwebsite la convierte a celdas reales de Excel, en vez de armar cada
 * celda a mano con WithEvents/WithStyles.
 */
class TareaExport implements FromView, ShouldAutoSize, WithTitle
{
    public function __construct(
        private readonly Tarea $tarea,
        private readonly ExportacionTareaService $exportacion,
    ) {
    }

    public function view(): View
    {
        return view("exports.tarea", [
            "tarea" => $this->tarea,
            "datosGenerales" => $this->exportacion->datosGenerales($this->tarea),
            "subtareas" => $this->exportacion->subtareas($this->tarea),
            "historial" => $this->exportacion->historial($this->tarea),
        ]);
    }

    public function title(): string
    {
        // Excel trunca/rechaza nombres de hoja de mas de 31 caracteres.
        return mb_substr($this->tarea->codigo, 0, 31);
    }
}
