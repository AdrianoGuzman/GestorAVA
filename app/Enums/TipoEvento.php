<?php

namespace App\Enums;

enum TipoEvento: string
{
    case Creacion = 'creacion';
    case Reasignacion = 'reasignacion';
    case ReasignacionExcepcional = 'reasignacion_excepcional';
    case ColaboradorAgregado = 'colaborador_agregado';
    case TransicionAutomatica = 'transicion_automatica';
    case Completada = 'completada';
    case Retroceso = 'retroceso';
    case ProblemaReportado = 'problema_reportado';
    case Cancelacion = 'cancelacion';
    case DependenciaCreada = 'dependencia_creada';
    case DependenciaResuelta = 'dependencia_resuelta';
    case ChecklistItemCreado = 'checklist_item_creado';
    case ChecklistItemMarcado = 'checklist_item_marcado';
    case ChecklistItemDesmarcado = 'checklist_item_desmarcado';
    case ChecklistItemEditado = 'checklist_item_editado';
    case ChecklistItemEliminado = 'checklist_item_eliminado';
    case TareaHijaCreada = 'tarea_hija_creada';
    case AdjuntoAgregado = 'adjunto_agregado';
    case NoParticipacionReportada = 'no_participacion_reportada';
    case TareaAtrasada = 'tarea_atrasada';
    case TareaEditada = 'tarea_editada';
    case TareaProximaAVencer = 'tarea_proxima_a_vencer';

    /** Mismo texto que ETIQUETAS_EVENTO en resources/js/components/tareas/historial-timeline.tsx -- usado en exportes PDF/Excel. */
    public function label(): string
    {
        return match ($this) {
            self::Creacion => "Creó la tarea",
            self::Reasignacion => "Reasignó el responsable",
            self::ReasignacionExcepcional => "Reasignó el responsable (excepción)",
            self::ColaboradorAgregado => "Agregó un colaborador",
            self::TransicionAutomatica => "La tarea pasó a En progreso automáticamente",
            self::Completada => "Marcó la tarea como completada",
            self::Retroceso => "Retrocedió la tarea a Pendiente",
            self::ProblemaReportado => "Reportó un problema en la tarea",
            self::NoParticipacionReportada => "Avisó que no puede seguir participando",
            self::Cancelacion => "Canceló la tarea",
            self::DependenciaCreada => "Creó una dependencia",
            self::DependenciaResuelta => "Resolvió una dependencia",
            self::ChecklistItemCreado => "Agregó una subtarea",
            self::ChecklistItemMarcado => "Marcó una subtarea como hecha",
            self::ChecklistItemDesmarcado => "Desmarcó una subtarea",
            self::ChecklistItemEditado => "Editó una subtarea",
            self::ChecklistItemEliminado => "Eliminó una subtarea",
            self::TareaHijaCreada => "Creó una tarea hija",
            self::AdjuntoAgregado => "Adjuntó un archivo",
            self::TareaAtrasada => "Se marcó como atrasada automáticamente",
            self::TareaEditada => "Editó la tarea",
            self::TareaProximaAVencer => "Se avisó que la tarea está por vencer",
        };
    }
}
