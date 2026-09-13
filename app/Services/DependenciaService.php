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
    ) {
    }

    /**
     * RF-21: crea una tarea hija de $tareaPadre -- una tarea normal (con su
     * propio responsable y seguimiento) pero con tarea_padre_id seteado.
     * Reusa TareaService::crear() en vez de duplicar su logica (responsable
     * por defecto, colaboradores, notificaciones); solo agrega el vinculo con
     * el padre y un evento en el historial del padre para que quede claro de
     * donde salio esa tarea.
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

        return $tareaHija;
    }
}
