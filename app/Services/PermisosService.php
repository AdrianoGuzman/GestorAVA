<?php

namespace App\Services;

use App\Models\ChecklistItem;
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

    /**
     * Editar titulo/descripcion/fechas: el responsable actual o quien creo
     * la tarea -- mismo criterio de cercania/autoridad que completar o
     * cancelar, no se extiende a colaboradores.
     */
    public function puedeEditar(Tarea $tarea, User $solicitante): bool
    {
        return $solicitante->id === $tarea->responsable_id
            || $solicitante->id === $tarea->creador_id;
    }

    /**
     * RF-12 D1: el responsable o un colaborador de la tarea puede
     * retrocederla de En progreso a Pendiente.
     */
    public function puedeRetroceder(Tarea $tarea, User $solicitante): bool
    {
        return $solicitante->id === $tarea->responsable_id
            || $tarea->colaboradores->contains("id", $solicitante->id);
    }

    /**
     * RF-13 (rediseñado) D1: el responsable o un colaborador de la tarea
     * puede reportar que está mal definida.
     */
    public function puedeReportarProblema(Tarea $tarea, User $solicitante): bool
    {
        return $solicitante->id === $tarea->responsable_id
            || $tarea->colaboradores->contains("id", $solicitante->id);
    }

    /**
     * El responsable o un colaborador puede avisar que no puede o no quiere
     * seguir participando en la tarea. Solo notifica, no cambia nada por su
     * cuenta (a diferencia de agregar/quitar colaboradores, RF-06).
     */
    public function puedeReportarNoParticipacion(Tarea $tarea, User $solicitante): bool
    {
        return $solicitante->id === $tarea->responsable_id
            || $tarea->colaboradores->contains("id", $solicitante->id);
    }

    /**
     * "Mi checklist" personal: el responsable o un colaborador de la tarea
     * puede tener su propia guia privada, sin dueño que asignar (siempre es
     * uno mismo) y sin bloquear nada.
     */
    public function puedeUsarChecklistPersonal(Tarea $tarea, User $solicitante): bool
    {
        return $solicitante->id === $tarea->responsable_id
            || $tarea->colaboradores->contains("id", $solicitante->id);
    }

    /**
     * RF-23 (Jeremy): el responsable o un colaborador de la tarea puede
     * usar el checklist compartido -- crear items, editar su texto,
     * eliminarlos. Asignar el dueño de un item es una accion distinta, ver
     * puedeAsignarDuenoChecklist().
     */
    public function puedeUsarChecklist(Tarea $tarea, User $solicitante): bool
    {
        return $solicitante->id === $tarea->responsable_id
            || $tarea->colaboradores->contains("id", $solicitante->id);
    }

    /**
     * Decision de Franco (11-09-2026): solo el creador de la tarea asigna
     * el dueño de un item del checklist -- no hay autoasignacion por parte
     * de un colaborador, para evitar confusion en la interfaz.
     */
    public function puedeAsignarDuenoChecklist(Tarea $tarea, User $solicitante): bool
    {
        return $solicitante->id === $tarea->creador_id;
    }

    /**
     * Decision de Franco (11-09-2026): solo el dueño asignado de un item
     * puede marcarlo/desmarcarlo -- evita que otra persona declare
     * terminada una parte de trabajo que no es suya. Si el item no tiene
     * dueño, cualquiera con acceso al checklist puede marcarlo (si no,
     * quedaría imposible de completar).
     */
    public function puedeMarcarChecklistItem(ChecklistItem $item, User $solicitante): bool
    {
        if ($item->dueno_id !== null) {
            return $solicitante->id === $item->dueno_id;
        }

        return $this->puedeUsarChecklist($item->tarea, $solicitante);
    }

    /**
     * RF-25: solo el responsable principal puede cancelar la tarea (mismo
     * criterio de rendición de cuentas que RF-11); no se extiende al
     * superior de unidad como sí ocurre con la reasignación de RF-05.
     */
    public function puedeCancelar(Tarea $tarea, User $solicitante): bool
    {
        return $solicitante->id === $tarea->responsable_id;
    }

    /**
     * RF-19 D1: el responsable o un colaborador de la tarea puede adjuntar
     * archivos.
     */
    public function puedeAdjuntar(Tarea $tarea, User $solicitante): bool
    {
        return $solicitante->id === $tarea->responsable_id
            || $tarea->colaboradores->contains("id", $solicitante->id);
    }
}
