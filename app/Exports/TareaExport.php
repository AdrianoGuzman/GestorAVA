<?php

namespace App\Exports;

use App\Models\Tarea;
use App\Services\ExportacionTareaService;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * Una sola hoja con las 3 tablas apiladas (datos generales, subtareas,
 * historial) -- decision de Franco, mas simple de abrir e imprimir de un
 * vistazo que repartirlas en pestañas separadas. FromView deja escribir la
 * hoja como una tabla HTML normal (mismo Blade que se veria en un navegador)
 * y Maatwebsite la convierte a celdas reales de Excel, en vez de armar cada
 * celda a mano con WithEvents/WithStyles.
 */
class TareaExport implements FromView, WithColumnWidths, WithEvents, WithTitle
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

    /**
     * Anchos fijos en vez de ShouldAutoSize: con el detalle del historial
     * ahora multilinea (Franco 13-09-2026 D2: mismo nivel de detalle que el
     * timeline interactivo), autosize mide la linea mas larga y deja una
     * columna absurdamente ancha en vez de dejar que el texto haga wrap.
     */
    public function columnWidths(): array
    {
        return ["A" => 24, "B" => 28, "C" => 22, "D" => 55];
    }

    /** Sin esto, un detalle de varias lineas (separadas con <br> en el Blade) queda todo en una sola linea visual, cortado por el ancho de columna. */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $evento) {
                $evento->sheet->getDelegate()
                    ->getStyle("A1:D{$evento->sheet->getHighestRow()}")
                    ->getAlignment()
                    ->setWrapText(true);
            },
        ];
    }
}
