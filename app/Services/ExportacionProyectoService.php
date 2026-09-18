<?php

namespace App\Services;

use App\Enums\TipoEventoProyecto;
use App\Models\HistorialProyecto;
use App\Models\Proyecto;
use App\Models\Seccion;
use App\Models\Tarea;
use Illuminate\Support\Carbon;

/**
 * Prepara las mismas 4 tablas (datos generales, secciones, tareas, historial)
 * para el export a PDF (ExportarProyectoController) y a Excel (ProyectoExport)
 * -- una sola fuente de verdad, mismo criterio que ExportacionTareaService.
 */
class ExportacionProyectoService
{
    private const SIN_VALOR = "(vacío)";

    public function __construct(private readonly AvanceProyectoService $avance)
    {
    }

    public function datosGenerales(Proyecto $proyecto): array
    {
        return [
            "Nombre" => $proyecto->nombre,
            "Estado" => $proyecto->estado->label(),
            "Avance total" => $this->formatearPorcentaje($this->avance->avanceProyecto($proyecto)),
            "Creado por" => $proyecto->creador?->name ?? "—",
            "Fecha inicio" => $proyecto->fecha_inicio?->format("d-m-Y") ?? "—",
            "Fecha término" => $proyecto->fecha_termino?->format("d-m-Y") ?? "—",
            "Descripción" => $proyecto->descripcion ?: "—",
        ];
    }

    public function secciones(Proyecto $proyecto): array
    {
        return $proyecto->secciones
            ->map(fn (Seccion $seccion) => [
                "nombre" => $seccion->nombre,
                "peso" => $this->formatearPorcentaje($seccion->peso),
                "avance" => $this->formatearPorcentaje($this->avance->avanceSimple($seccion->tareas)),
                "tareas" => (string) $seccion->tareas->count(),
            ])
            ->values()
            ->all();
    }

    /** Todas las tareas del proyecto, agrupadas visualmente por sección (incluye "Sin sección"). */
    public function tareas(Proyecto $proyecto): array
    {
        $filas = $proyecto->secciones->flatMap(
            fn (Seccion $seccion) => $seccion->tareas->map(fn (Tarea $tarea) => $this->filaTarea($tarea, $seccion->nombre))
        );

        $filas = $filas->merge($proyecto->tareas->map(fn (Tarea $tarea) => $this->filaTarea($tarea, "Sin sección")));

        return $filas->values()->all();
    }

    private function filaTarea(Tarea $tarea, string $seccionNombre): array
    {
        return [
            "código" => $tarea->codigo,
            "título" => $tarea->titulo,
            "sección" => $seccionNombre,
            "estado" => $tarea->estado->label(),
            "responsable" => $tarea->responsable?->name ?? "—",
        ];
    }

    /**
     * Mismo nivel de detalle que historial-proyecto-timeline.tsx (Franco
     * 14-09-2026, mismo criterio que "Idea A"/"igual de detallado" de Tarea):
     * cada fila trae las lineas de "Campo: anterior → actual" que ya arma el
     * timeline interactivo. Si el frontend cambia esa logica, hay que
     * actualizar las dos.
     */
    public function historial(Proyecto $proyecto): array
    {
        return $proyecto->historial
            ->map(function (HistorialProyecto $evento) {
                $detalle = $this->detalles($evento);

                return [
                    "fecha" => $evento->created_at->format("d-m-Y H:i"),
                    "usuario" => $evento->usuario?->name ?? "Sistema",
                    "evento" => $evento->tipo_evento->label(),
                    "detalle" => $detalle === [] ? ["—"] : $detalle,
                ];
            })
            ->values()
            ->all();
    }

    /** @return string[] */
    private function detalles(HistorialProyecto $evento): array
    {
        $datos = $evento->datos_evento ?? [];

        return match ($evento->tipo_evento) {
            TipoEventoProyecto::Creacion => [
                "Fecha inicio: {$this->formatearFecha($datos['fecha_inicio'] ?? null)}",
                "Fecha término: {$this->formatearFecha($datos['fecha_termino'] ?? null)}",
            ],

            TipoEventoProyecto::ProyectoEditado => $this->diffCampos($datos["datos_anteriores"] ?? null, $datos, [
                ["nombre", "Nombre", fn ($v) => $this->formatearTexto($v, 60)],
                ["descripcion", "Descripción", fn ($v) => $this->formatearTexto($v, 50)],
                ["estado", "Estado", fn ($v) => $v === "cerrado" ? "Cerrado" : ($v === "activo" ? "Activo" : self::SIN_VALOR)],
                ["fecha_inicio", "Fecha inicio", fn ($v) => $this->formatearFecha($v)],
                ["fecha_termino", "Fecha término", fn ($v) => $this->formatearFecha($v)],
            ]),

            TipoEventoProyecto::SeccionCreada => isset($datos["seccion_nombre"])
                ? ["Sección: {$this->formatearTexto($datos['seccion_nombre'], 60)} (peso {$this->formatearPorcentaje($datos['peso'] ?? 0)})"]
                : [],

            TipoEventoProyecto::SeccionEditada => $this->diffCampos($datos["datos_anteriores"] ?? null, $datos, [
                ["nombre", "Nombre", fn ($v) => $this->formatearTexto($v, 60)],
                ["peso", "Peso", fn ($v) => $this->formatearPorcentaje($v)],
            ]),

            // Sin diff: igual que en historial-proyecto-timeline.tsx, este
            // evento ya se llama "Aplazó la entrega" -- la fecha vieja no
            // aporta, solo la nueva.
            TipoEventoProyecto::EntregaAplazada => [
                "Fecha término: {$this->formatearFecha($datos['fecha_termino'] ?? null)}",
                "Motivo: {$this->formatearTexto($datos['motivo'] ?? null, 200)}",
            ],
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

    private function formatearPorcentaje(mixed $valor): string
    {
        return is_numeric($valor) ? round(((float) $valor) * 100)."%" : self::SIN_VALOR;
    }
}
