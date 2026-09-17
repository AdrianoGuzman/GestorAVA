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
     * la misma unidad organizacional que la tarea (RN-11). Decision de
     * Franco (13-09-2026): no aplica sobre una tarea ya completada o
     * cancelada.
     */
    public function puedeReasignar(Tarea $tarea, User $solicitante): bool
    {
        return ! $tarea->estado->esTerminal()
            && ($solicitante->id === $tarea->responsable_id || $this->esSuperiorDirectoDeUnidad($tarea, $solicitante));
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
        if ($tarea->estado->esTerminal()) {
            return false;
        }

        $responsable = $tarea->responsable;

        if (! $responsable?->nivel_jerarquico || ! $usuario->nivel_jerarquico) {
            return false;
        }

        return $usuario->nivel_jerarquico->esSuperiorA($responsable->nivel_jerarquico);
    }

    /**
     * RF-06 D2 (17-09-2026): solo el creador o el responsable de la tarea
     * pueden agregar nuevos colaboradores -- reemplaza la decision de
     * Franco del 13-09-2026, que tambien lo permitia a cualquier
     * colaborador ya existente. Sin restriccion de nivel jerarquico ni de
     * unidad organizacional (RN-04). No aplica sobre una tarea ya
     * completada o cancelada (ver EstadoTarea::esTerminal()).
     */
    public function puedeAgregarColaborador(Tarea $tarea, User $solicitante): bool
    {
        return ! $tarea->estado->esTerminal()
            && ($solicitante->id === $tarea->creador_id || $solicitante->id === $tarea->responsable_id);
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
     * Para la UI: ver puedeMostrarCancelar -- oculta "Completar" si la tarea
     * ya esta en un estado terminal. El guard real sigue siendo el
     * estado->puedeTransicionarA() de FinalizacionService.
     */
    public function puedeMostrarCompletar(Tarea $tarea, User $solicitante): bool
    {
        return $this->puedeCompletar($tarea, $solicitante) && ! $tarea->estado->esTerminal();
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
     * Para la UI: ver puedeMostrarCancelar -- oculta el lapiz de editar si
     * la tarea ya esta en un estado terminal. El guard real sigue siendo el
     * chequeo de estado en TareaService::actualizar().
     */
    public function puedeMostrarEditar(Tarea $tarea, User $solicitante): bool
    {
        return $this->puedeEditar($tarea, $solicitante) && ! $tarea->estado->esTerminal();
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
     * puede reportar que está mal definida. Decision de Franco
     * (13-09-2026): no aplica sobre una tarea ya completada o cancelada --
     * una vez cerrada no tiene sentido seguir notificando sobre su
     * definición. Tampoco aplica si el responsable es tambien el creador
     * (ver mismaPersonaEnAmbosExtremos()) -- ahi el destinatario siempre
     * seria el mismo que reporta, y esa persona ya puede corregir la
     * definicion editando la tarea directamente.
     */
    public function puedeReportarProblema(Tarea $tarea, User $solicitante): bool
    {
        return ! $tarea->estado->esTerminal()
            && ! $this->mismaPersonaEnAmbosExtremos($tarea, $solicitante)
            && ($solicitante->id === $tarea->responsable_id || $tarea->colaboradores->contains("id", $solicitante->id));
    }

    /**
     * El responsable o un colaborador puede avisar que no puede o no quiere
     * seguir participando en la tarea. Solo notifica, no cambia nada por su
     * cuenta (a diferencia de agregar/quitar colaboradores, RF-06). Decision
     * de Franco (13-09-2026): no aplica sobre una tarea ya completada o
     * cancelada, ni si el responsable es tambien el creador (mismo motivo
     * que en puedeReportarProblema: no hay a quien avisar, y esa persona ya
     * puede reasignar la tarea si no puede seguir con ella).
     */
    public function puedeReportarNoParticipacion(Tarea $tarea, User $solicitante): bool
    {
        return ! $tarea->estado->esTerminal()
            && ! $this->mismaPersonaEnAmbosExtremos($tarea, $solicitante)
            && ($solicitante->id === $tarea->responsable_id || $tarea->colaboradores->contains("id", $solicitante->id));
    }

    /**
     * Reportar problema / avisar no participacion notifican "al otro
     * extremo": si reporta el responsable, le llega al creador (ver
     * ReporteProblemaService/NoParticipacionService::obtenerDestinatario()).
     * Si esa misma persona es responsable Y creador, ese destinatario es
     * ella misma -- el unico caso real donde eso pasa, porque un colaborador
     * que ademas sea creador SI notifica a un responsable distinto.
     */
    private function mismaPersonaEnAmbosExtremos(Tarea $tarea, User $solicitante): bool
    {
        return $solicitante->id === $tarea->responsable_id && $tarea->responsable_id === $tarea->creador_id;
    }

    /**
     * "Mi checklist" personal: el responsable o un colaborador de la tarea
     * puede tener su propia guia privada, sin dueño que asignar (siempre es
     * uno mismo) y sin bloquear nada. Decision de Franco (13-09-2026): no
     * aplica sobre una tarea ya completada o cancelada.
     */
    public function puedeUsarChecklistPersonal(Tarea $tarea, User $solicitante): bool
    {
        return ! $tarea->estado->esTerminal()
            && ($solicitante->id === $tarea->responsable_id || $tarea->colaboradores->contains("id", $solicitante->id));
    }

    /**
     * RF-23 (Jeremy): el responsable o un colaborador de la tarea puede
     * usar el checklist compartido -- crear items, editar su texto,
     * eliminarlos. Asignar el dueño de un item es una accion distinta, ver
     * puedeAsignarDuenoChecklist(). Decision de Franco (13-09-2026): no
     * aplica sobre una tarea ya completada o cancelada.
     */
    public function puedeUsarChecklist(Tarea $tarea, User $solicitante): bool
    {
        return ! $tarea->estado->esTerminal()
            && ($solicitante->id === $tarea->responsable_id || $tarea->colaboradores->contains("id", $solicitante->id));
    }

    /**
     * Decision de Franco (12-09-2026): el responsable o el creador de la
     * tarea asignan el dueño de un item del checklist -- no hay
     * autoasignacion por parte de un colaborador, para evitar confusion en
     * la interfaz.
     */
    public function puedeAsignarDuenoChecklist(Tarea $tarea, User $solicitante): bool
    {
        return $solicitante->id === $tarea->responsable_id
            || $solicitante->id === $tarea->creador_id;
    }

    /**
     * Decision de Franco (11-09-2026): solo el dueño asignado de un item
     * puede marcarlo/desmarcarlo -- evita que otra persona declare
     * terminada una parte de trabajo que no es suya. Si el item no tiene
     * dueño, cualquiera con acceso al checklist puede marcarlo (si no,
     * quedaría imposible de completar). El chequeo de dueño ignora el
     * estado de la tarea (es una relacion directa, no pasa por
     * puedeUsarChecklist), asi que el estado terminal se valida aparte acá
     * (13-09-2026) para que ni el dueño pueda seguir marcando items de una
     * tarea ya cerrada.
     */
    public function puedeMarcarChecklistItem(ChecklistItem $item, User $solicitante): bool
    {
        if ($item->tarea->estado->esTerminal()) {
            return false;
        }

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
     * Para la UI: ademas del permiso de rol (puedeCancelar), oculta el boton
     * "Cancelar" si la tarea ya esta en un estado terminal. El guard que
     * efectivamente bloquea el envio sigue siendo el
     * estado->puedeTransicionarA() de CancelacionService (con su propio
     * mensaje especifico) -- este metodo no lo reemplaza, solo evita ofrecer
     * en pantalla una accion que el backend va a rechazar.
     */
    public function puedeMostrarCancelar(Tarea $tarea, User $solicitante): bool
    {
        return $this->puedeCancelar($tarea, $solicitante) && ! $tarea->estado->esTerminal();
    }

    /**
     * RF-19 D1: el responsable o un colaborador de la tarea puede adjuntar
     * archivos. Decision de Franco (13-09-2026): no aplica sobre una tarea
     * ya completada o cancelada.
     */
    public function puedeAdjuntar(Tarea $tarea, User $solicitante): bool
    {
        return ! $tarea->estado->esTerminal()
            && ($solicitante->id === $tarea->responsable_id || $tarea->colaboradores->contains("id", $solicitante->id));
    }

    /**
     * RF-21: el responsable o un colaborador de la tarea padre puede crear
     * una tarea hija para delegar una parte del trabajo -- mismo nivel de
     * confianza que usar el checklist (puedeUsarChecklist), ya que ambas son
     * formas de dividir el trabajo de la tarea. Decision de Franco
     * (13-09-2026): no aplica si la tarea padre ya esta completada o
     * cancelada.
     */
    public function puedeCrearTareaHija(Tarea $tareaPadre, User $solicitante): bool
    {
        return ! $tareaPadre->estado->esTerminal()
            && ($solicitante->id === $tareaPadre->responsable_id || $tareaPadre->colaboradores->contains("id", $solicitante->id));
    }
}
