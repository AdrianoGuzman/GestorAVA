<?php

namespace App\Services;

use App\Enums\PrioridadTarea;
use App\Enums\TipoEvento;
use App\Models\HistorialTarea;
use App\Models\Proyecto;
use App\Models\Seccion;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Support\Carbon;

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

    private const SIN_VALOR = "(vacío)";

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
            "Proyecto" => $tarea->proyecto?->nombre ?? "—",
            "Sección" => $tarea->seccion?->nombre ?? "—",
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

    /**
     * Mismo nivel de detalle que historial-timeline.tsx (construirDetalles()
     * en el frontend, Franco 13-09-2026 D2: "igual de detallado en los
     * documentos"): cada fila trae, ademas del motivo, las lineas de "Campo:
     * anterior → actual" que ya arma el timeline interactivo -- misma fuente
     * de datos (datos_evento/datos_anteriores), misma logica, portada a PHP
     * porque un documento exportado no puede llamar al codigo del navegador.
     * Si el frontend cambia esta logica, hay que actualizar las dos.
     */
    public function historial(Tarea $tarea): array
    {
        // "name" es un accessor (nombre completo armado desde nombre_1/apellido_1,
        // etc.), no una columna real -- pluck() de query builder no lo ve y falla
        // en SQL. Hay que traer los modelos y recien ahi pluckear en PHP.
        $nombrePorId = User::all()->pluck("name", "id");
        $proyectoPorId = Proyecto::all()->pluck("nombre", "id");
        $seccionPorId = Seccion::all()->pluck("nombre", "id");

        return $tarea->historial
            ->reject(fn ($evento) => in_array($evento->tipo_evento, self::EVENTOS_OCULTOS, true))
            ->map(function (HistorialTarea $evento) use ($nombrePorId, $proyectoPorId, $seccionPorId) {
                $lineas = [];

                $motivo = $evento->datos_evento["motivo"] ?? $evento->datos_evento["motivo_excepcion"] ?? null;
                if ($motivo) {
                    $lineas[] = "Motivo: {$motivo}";
                }

                array_push($lineas, ...$this->detalles($evento, $nombrePorId, $proyectoPorId, $seccionPorId));

                return [
                    "fecha" => $evento->created_at->format("d-m-Y H:i"),
                    "usuario" => $evento->usuario?->name ?? "Sistema",
                    "evento" => $evento->tipo_evento->label(),
                    "detalle" => $lineas === [] ? ["—"] : $lineas,
                ];
            })
            ->values()
            ->all();
    }

    /** @return string[] */
    private function detalles(
        HistorialTarea $evento,
        \Illuminate\Support\Collection $nombrePorId,
        \Illuminate\Support\Collection $proyectoPorId,
        \Illuminate\Support\Collection $seccionPorId,
    ): array {
        $datos = $evento->datos_evento ?? [];
        $nombreDe = fn ($id) => is_int($id) ? ($nombrePorId[$id] ?? "Usuario #{$id}") : self::SIN_VALOR;
        $proyectoDe = fn ($id) => $id === null ? "Sin proyecto" : ($proyectoPorId[$id] ?? "Proyecto #{$id}");
        $seccionDe = fn ($id) => $id === null ? "Sin sección" : ($seccionPorId[$id] ?? "Sección #{$id}");

        return match ($evento->tipo_evento) {
            TipoEvento::TareaEditada => $this->diffCampos($datos["datos_anteriores"] ?? null, $datos, [
                ["titulo", "Título", fn ($v) => $this->formatearTexto($v, 60)],
                ["descripcion", "Descripción", fn ($v) => $this->formatearTexto($v, 50)],
                ["fecha_inicio", "Fecha inicio", fn ($v) => $this->formatearFecha($v)],
                ["fecha_compromiso", "Fecha término", fn ($v) => $this->formatearFecha($v)],
                ["prioridad", "Prioridad", fn ($v) => $this->formatearPrioridad($v)],
                ["evidencia_obligatoria", "Evidencia obligatoria", fn ($v) => $v ? "Sí" : "No"],
                ["proyecto_id", "Proyecto", fn ($v) => $proyectoDe($v)],
                ["seccion_id", "Sección", fn ($v) => $seccionDe($v)],
            ]),

            TipoEvento::ChecklistItemEditado => $this->diffCampos($datos["datos_anteriores"] ?? null, $datos, [
                ["texto", "Texto", fn ($v) => $this->formatearTexto($v, 60)],
                ["dueno_id", "Dueño", fn ($v) => $v === null ? "Sin dueño" : $nombreDe($v)],
                ["fecha_limite", "Fecha límite", fn ($v) => $this->formatearFecha($v)],
            ]),

            TipoEvento::ChecklistItemCreado => array_filter([
                isset($datos["texto"]) ? "Subtarea: {$this->formatearTexto($datos['texto'], 60)}" : null,
                isset($datos["dueno_id"]) && is_int($datos["dueno_id"]) ? "Dueño: {$nombreDe($datos['dueno_id'])}" : null,
            ]),

            TipoEvento::ChecklistItemEliminado => isset($datos["texto"])
                ? ["Subtarea: {$this->formatearTexto($datos['texto'], 60)}"]
                : [],

            TipoEvento::Reasignacion, TipoEvento::ReasignacionExcepcional => array_filter([
                "Responsable: {$nombreDe($datos['responsable_anterior_id'] ?? null)} → {$nombreDe($datos['responsable_nuevo_id'] ?? null)}",
                ($datos["mantuvo_como_colaborador"] ?? false) === true ? "Responsable saliente: quedó como colaborador" : null,
            ]),

            TipoEvento::ColaboradorAgregado => isset($datos["colaborador_id"])
                ? ["Colaborador agregado: {$nombreDe($datos['colaborador_id'])}"]
                : [],

            TipoEvento::TareaHijaCreada => isset($datos["titulo"])
                ? ["Tarea hija: {$datos['titulo']}"]
                : [],

            default => [],
        };
    }

    /**
     * @param  array{0: string, 1: string, 2: callable}[]  $campos  [clave, etiqueta, formateador]
     * @return string[]
     */
    private function diffCampos(?array $antes, array $despues, array $campos): array
    {
        if ($antes === null) {
            return [];
        }

        $lineas = [];
        foreach ($campos as [$clave, $etiqueta, $formatear]) {
            $valorAntes = $antes[$clave] ?? null;
            $valorDespues = $despues[$clave] ?? null;

            if ($valorAntes === $valorDespues) {
                continue;
            }

            $lineas[] = "{$etiqueta}: {$formatear($valorAntes)} → {$formatear($valorDespues)}";
        }

        return $lineas;
    }

    private function formatearTexto(mixed $valor, int $max): string
    {
        if ($valor === null || $valor === "") {
            return self::SIN_VALOR;
        }

        $texto = (string) $valor;

        return mb_strlen($texto) > $max ? mb_substr($texto, 0, $max)."…" : $texto;
    }

    private function formatearFecha(mixed $valor): string
    {
        if (! is_string($valor) || $valor === "") {
            return self::SIN_VALOR;
        }

        return Carbon::parse(mb_substr($valor, 0, 10))->format("d-m-Y");
    }

    private function formatearPrioridad(mixed $valor): string
    {
        return is_string($valor) ? (PrioridadTarea::tryFrom($valor)?->label() ?? self::SIN_VALOR) : self::SIN_VALOR;
    }
}
