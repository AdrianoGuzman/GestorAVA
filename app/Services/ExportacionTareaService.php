<?php

namespace App\Services;

use App\Enums\TipoEvento;
use App\Models\Tarea;

/**
 * Prepara las mismas 3 tablas (datos generales, subtareas, historial) para
 * el export a PDF (ExportarTareaController) y a Excel (TareaExport) -- una
 * sola fuente de verdad en vez de duplicar el armado de filas en dos lados.
 * Solo incluye el checklist compartido (RF-23, "Subtareas"), no "Mi
 * checklist": ese es privado de quien lo mira, no forma parte del registro
 * de la tarea que alguien mas podria abrir en el PDF/Excel exportado.
 */
class ExportacionTareaService
{
    private const EVENTOS_OCULTOS = [TipoEvento::ChecklistItemMarcado, TipoEvento::ChecklistItemDesmarcado];

    public function datosGenerales(Tarea $tarea): array
    {
        return [
            "Código" => $tarea->codigo,
            "Título" => $tarea->titulo,
            "Estado" => $tarea->estado->label(),
            "Prioridad" => $tarea->prioridad->label(),
            "Atrasada" => $tarea->esta_atrasada ? "Sí" : "No",
            "Responsable" => $tarea->responsable?->name ?? "—",
            "Colaboradores" => $tarea->colaboradores->isEmpty() ? "—" : $tarea->colaboradores->pluck("name")->implode(", "),
            "Creador" => $tarea->creador?->name ?? "—",
            "Fecha inicio" => $tarea->fecha_inicio?->format("d-m-Y") ?? "—",
            "Fecha término" => $tarea->fecha_compromiso->format("d-m-Y"),
            "Descripción" => $tarea->descripcion ?: "—",
        ];
    }

    public function subtareas(Tarea $tarea): array
    {
        return $tarea->checklistItems
            ->map(fn ($item) => [
                "texto" => $item->texto,
                "dueño" => $item->dueno?->name ?? "—",
                "fecha límite" => $item->fecha_limite?->format("d-m-Y") ?? "—",
                "completada" => $item->completado ? "Sí" : "No",
            ])
            ->values()
            ->all();
    }

    public function historial(Tarea $tarea): array
    {
        return $tarea->historial
            ->reject(fn ($evento) => in_array($evento->tipo_evento, self::EVENTOS_OCULTOS, true))
            ->map(fn ($evento) => [
                "fecha" => $evento->created_at->format("d-m-Y H:i"),
                "usuario" => $evento->usuario?->name ?? "Sistema",
                "evento" => $evento->tipo_evento->label(),
                "motivo" => $evento->datos_evento["motivo"] ?? $evento->datos_evento["motivo_excepcion"] ?? "—",
            ])
            ->values()
            ->all();
    }
}
