<?php

namespace App\Services;

use App\Models\Tarea;
use App\Models\User;

/**
 * RN-11 / RN-12: quien puede reasignar el responsable de una tarea, y quien
 * puede autorizar una reasignacion excepcional por ausencia total.
 */
class PermisosService
{
    /**
     * RF-05 D1: el responsable actual, o su superior jerarquico directo de
     * la misma unidad organizacional que la tarea (RN-11).
     */
    public function puedeReasignar(Tarea $tarea, User $solicitante): bool
    {
        return $solicitante->id === $tarea->responsable_id
            || $this->esSuperiorDirectoDeUnidad($tarea, $solicitante);
    }

    public function esSuperiorDirectoDeUnidad(Tarea $tarea, User $usuario): bool
    {
        $responsable = $tarea->responsable;

        if (! $responsable?->nivel_jerarquico || ! $usuario->nivel_jerarquico) {
            return false;
        }

        if ($usuario->nivel_jerarquico !== $responsable->nivel_jerarquico->nivelSuperior()) {
            return false;
        }

        if ($usuario->unidad_organizacional_id === $tarea->unidad_organizacional_id) {
            return true;
        }

        return $usuario->unidad_organizacional_id !== null
            && $usuario->unidad_organizacional_id === $tarea->unidadOrganizacional?->unidad_padre_id;
    }

    /**
     * RN-12: si el responsable y su superior directo de unidad estan ambos
     * indisponibles, cualquier nivel jerarquico estrictamente superior al del
     * responsable puede autorizar la reasignacion como excepcion, sin
     * restriccion de unidad organizacional. El Sprint 1 no valida
     * automaticamente la indisponibilidad (no hay modelo de "usuario
     * inactivo"): es una autorizacion manual y de confianza.
     */
    public function puedeAutorizarExcepcion(Tarea $tarea, User $usuario): bool
    {
        $responsable = $tarea->responsable;

        if (! $responsable?->nivel_jerarquico || ! $usuario->nivel_jerarquico) {
            return false;
        }

        return $usuario->nivel_jerarquico->esSuperiorA($responsable->nivel_jerarquico);
    }

    /**
     * RF-06 D1: el responsable de la tarea o cualquier colaborador ya
     * existente puede agregar nuevos colaboradores, sin restriccion de nivel
     * jerarquico ni de unidad organizacional (RN-04).
     */
    public function puedeAgregarColaborador(Tarea $tarea, User $solicitante): bool
    {
        return $solicitante->id === $tarea->responsable_id
            || $tarea->colaboradores->contains("id", $solicitante->id);
    }

    /**
     * RF-11 D1 / RN-14: solo el responsable principal puede marcar la tarea
     * como completada; un colaborador no tiene esta accion disponible.
     */
    public function puedeCompletar(Tarea $tarea, User $solicitante): bool
    {
        return $solicitante->id === $tarea->responsable_id;
    }
}
