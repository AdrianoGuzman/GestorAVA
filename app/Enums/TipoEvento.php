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
    case AdjuntoAgregado = 'adjunto_agregado';
    case NoParticipacionReportada = 'no_participacion_reportada';
    case TareaAtrasada = 'tarea_atrasada';
    case TareaEditada = 'tarea_editada';
}
