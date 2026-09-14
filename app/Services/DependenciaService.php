<?php

namespace App\Services;

use App\Enums\TipoEvento;
use App\Exceptions\PermisoDenegadoException;
use App\Models\Tarea;
use App\Models\User;

class DependenciaService
{
    public function __construct(
        private readonly TareaService $tareaService,
        private readonly HistorialService $historial,
        private readonly PermisosService $permisos,
        private readonly NotificacionService $notificaciones,
    ) {
    }

    /**
     * RF-21: crea una tarea hija de $tareaPadre -- una tarea normal (con su
     * propio responsable y seguimiento) pero con tarea_padre_id seteado.
     * Reusa TareaService::crear() en vez de duplicar su logica (responsable
     * por defecto, colaboradores, notificacion al responsable de la tarea
     * hija); esto solo agrega el vinculo con el padre, un evento en su
     * historial, y avisa al responsable de la tarea padre (EP-20) de que se
     * creo una dependencia a partir de su tarea -- salvo que el mismo la
     * haya creado.
     */
    public function crearTareaHija(Tarea $tareaPadre, array $datos, User $creador): Tarea
    {
        if (! $this->permisos->puedeCrearTareaHija($tareaPadre, $creador)) {
            throw new PermisoDenegadoException(
                $tareaPadre->estado->esTerminal()
                    ? "No se pueden crear tareas hijas de una tarea completada o cancelada."
                    : "Solo el responsable o un colaborador de la tarea puede crear una tarea hija."
            );
        }

        $tareaHija = $this->tareaService->crear(
            [...$datos, "tarea_padre_id" => $tareaPadre->id],
            $creador,
        );

        $this->historial->registrar($tareaPadre, TipoEvento::TareaHijaCreada, $creador, [
            "tarea_hija_id" => $tareaHija->id,
            "titulo" => $tareaHija->titulo,
        ]);

        if ($tareaPadre->responsable_id !== $creador->id) {
            $this->notificaciones->notificarDependenciaCreada($tareaPadre->responsable, $tareaPadre, $tareaHija, $creador);
        }

        return $tareaHija;
    }
}
