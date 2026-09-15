<?php

namespace App\Exports;

use App\Models\Proyecto;
use App\Services\ExportacionProyectoService;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

/** Una sola hoja con las 4 tablas apiladas (datos generales, secciones, tareas, historial) -- mismo criterio que TareaExport. */
class ProyectoExport implements FromView, WithColumnWidths, WithEvents, WithTitle
{
    public function __construct(
        private readonly Proyecto $proyecto,
        private readonly ExportacionProyectoService $exportacion,
    ) {
    }

    public function view(): View
    {
        return view("exports.proyecto", [
            "proyecto" => $this->proyecto,
            "datosGenerales" => $this->exportacion->datosGenerales($this->proyecto),
            "secciones" => $this->exportacion->secciones($this->proyecto),
            "tareas" => $this->exportacion->tareas($this->proyecto),
            "historial" => $this->exportacion->historial($this->proyecto),
        ]);
    }

    public function title(): string
    {
        // Excel trunca/rechaza nombres de hoja de mas de 31 caracteres.
        return mb_substr($this->proyecto->nombre, 0, 31);
    }

    /** Anchos fijos en vez de ShouldAutoSize -- mismo motivo que TareaExport (el detalle del historial es multilinea). 5 columnas: la tabla de Tareas es la que mas necesita. */
    public function columnWidths(): array
    {
        return ["A" => 22, "B" => 30, "C" => 18, "D" => 16, "E" => 26];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $evento) {
                $evento->sheet->getDelegate()
                    ->getStyle("A1:E{$evento->sheet->getHighestRow()}")
                    ->getAlignment()
                    ->setWrapText(true);
            },
        ];
    }
}
