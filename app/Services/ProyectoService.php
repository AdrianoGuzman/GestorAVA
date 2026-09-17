<?php

namespace App\Services;

use App\Enums\EstadoProyecto;
use App\Enums\TipoEventoProyecto;
use App\Exceptions\PermisoDenegadoException;
use App\Models\Proyecto;
use App\Models\Seccion;
use App\Models\User;

/**
 * Administracion de Proyectos (agrupan tareas de varias unidades bajo una
 * misma iniciativa estrategica). Solo Directorio/Gerencia pueden crear,
 * editar o cerrar uno -- ver NivelJerarquico::puedeAdministrarProyectos().
 */
class ProyectoService
{
    public function __construct(private readonly HistorialProyectoService $historial)
    {
    }

    public function crear(array $datos, User $actor): Proyecto
    {
        $this->verificarPermiso($actor);

        // "estado" explicito en vez de confiar en el default de la BD (activo):
        // Proyecto::create() no vuelve a leer la fila insertada, asi que el
        // objeto en memoria quedaria con estado null hasta el proximo query
        // -- mismo criterio que TareaService::crear() con "pendiente".
        $proyecto = Proyecto::create([
            "nombre" => $datos["nombre"],
            "descripcion" => $datos["descripcion"] ?? null,
            "creador_id" => $actor->id,
            "estado" => EstadoProyecto::Activo,
            "fecha_inicio" => $datos["fecha_inicio"],
            "fecha_termino" => $datos["fecha_termino"],
        ]);

        $this->historial->registrar($proyecto, TipoEventoProyecto::Creacion, $actor, [
            "nombre" => $proyecto->nombre,
            "descripcion" => $proyecto->descripcion,
            "fecha_inicio" => $proyecto->fecha_inicio->toDateString(),
            "fecha_termino" => $proyecto->fecha_termino->toDateString(),
        ]);

        return $proyecto;
    }

    /**
     * Franco (14-09-2026): "quien lo creó, cerró, agregó una sección" -- el
     * cambio de estado (cerrar/reabrir) no tiene un flujo propio como
     * completar/cancelar una tarea, es parte del mismo formulario de edición,
     * asi que se registra con el mismo diff campo a campo que el resto
     * (misma logica que TareaEditada, "Idea A" de Tarea).
     */
    public function actualizar(Proyecto $proyecto, array $datos, User $actor): Proyecto
    {
        $this->verificarPermiso($actor);

        $datosAnteriores = [
            "nombre" => $proyecto->nombre,
            "descripcion" => $proyecto->descripcion,
            "estado" => $proyecto->estado->value,
            "fecha_inicio" => $proyecto->fecha_inicio?->toDateString(),
            "fecha_termino" => $proyecto->fecha_termino?->toDateString(),
        ];

        $proyecto->update([
            "nombre" => $datos["nombre"],
            "descripcion" => $datos["descripcion"] ?? null,
            "estado" => $datos["estado"],
            "fecha_inicio" => $datos["fecha_inicio"],
            "fecha_termino" => $datos["fecha_termino"],
        ]);

        $this->historial->registrar($proyecto, TipoEventoProyecto::ProyectoEditado, $actor, [
            "datos_anteriores" => $datosAnteriores,
            "nombre" => $proyecto->nombre,
            "descripcion" => $proyecto->descripcion,
            "estado" => $proyecto->estado->value,
            "fecha_inicio" => $proyecto->fecha_inicio->toDateString(),
            "fecha_termino" => $proyecto->fecha_termino->toDateString(),
        ]);

        return $proyecto;
    }

    /**
     * Aplazar la entrega (AVA Montajes, 15-09-2026): a diferencia de
     * actualizar(), esto tiene su propio evento de historial (en vez de
     * "proyecto_editado" generico) porque lleva motivo obligatorio y solo
     * toca fecha_termino -- mismo criterio que RetrocesoService con Tarea.
     */
    public function aplazarEntrega(Proyecto $proyecto, array $datos, User $actor): Proyecto
    {
        $this->verificarPermiso($actor);

        $fechaAnterior = $proyecto->fecha_termino->toDateString();

        $proyecto->update(["fecha_termino" => $datos["fecha_termino"]]);

        $this->historial->registrar($proyecto, TipoEventoProyecto::EntregaAplazada, $actor, [
            "datos_anteriores" => ["fecha_termino" => $fechaAnterior],
            "fecha_termino" => $proyecto->fecha_termino->toDateString(),
            "motivo" => $datos["motivo"],
        ]);

        return $proyecto;
    }

    /** Mismo permiso que administrar el proyecto en si -- ver NivelJerarquico::puedeAdministrarProyectos(). */
    public function crearSeccion(Proyecto $proyecto, array $datos, User $actor): Seccion
    {
        $this->verificarPermiso($actor);

        $seccion = $proyecto->secciones()->create([
            "nombre" => $datos["nombre"],
            "peso" => $datos["peso"],
        ]);

        $this->historial->registrar($proyecto, TipoEventoProyecto::SeccionCreada, $actor, [
            "seccion_nombre" => $seccion->nombre,
            "peso" => $seccion->peso,
        ]);

        return $seccion;
    }

    public function actualizarSeccion(Seccion $seccion, array $datos, User $actor): Seccion
    {
        $this->verificarPermiso($actor);

        $datosAnteriores = [
            "nombre" => $seccion->nombre,
            "peso" => $seccion->peso,
        ];

        $seccion->update([
            "nombre" => $datos["nombre"],
            "peso" => $datos["peso"],
        ]);

        $this->historial->registrar($seccion->proyecto, TipoEventoProyecto::SeccionEditada, $actor, [
            "datos_anteriores" => $datosAnteriores,
            "nombre" => $seccion->nombre,
            "peso" => $seccion->peso,
        ]);

        return $seccion;
    }

    private function verificarPermiso(User $actor): void
    {
        if (! ($actor->nivel_jerarquico?->puedeAdministrarProyectos() ?? false)) {
            throw new PermisoDenegadoException("No tienes permiso para administrar proyectos.");
        }
    }
}
