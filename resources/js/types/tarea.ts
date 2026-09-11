export type EstadoTarea = 'pendiente' | 'en_progreso' | 'completada' | 'cancelada';

export type TipoEvento =
    | 'creacion'
    | 'reasignacion'
    | 'reasignacion_excepcional'
    | 'colaborador_agregado'
    | 'transicion_automatica'
    | 'completada'
    | 'retroceso'
    | 'problema_reportado'
    | 'no_participacion_reportada'
    | 'cancelacion'
    | 'dependencia_creada'
    | 'dependencia_resuelta'
    | 'checklist_item_creado'
    | 'checklist_item_marcado'
    | 'checklist_item_desmarcado'
    | 'checklist_item_editado'
    | 'checklist_item_eliminado'
    | 'adjunto_agregado';

export interface UsuarioTarea {
    id: number;
    name: string;
    email: string;
    [key: string]: unknown;
}

export interface HistorialEvento {
    id: number;
    tipo_evento: TipoEvento;
    usuario_id: number | null;
    usuario: UsuarioTarea | null;
    datos_evento: Record<string, unknown> | null;
    created_at: string;
}

export type CategoriaAdjunto = 'necesario' | 'evidencia';

export interface AdjuntoTarea {
    id: number;
    tarea_id: number;
    nombre_original: string;
    mime_type: string;
    tamano_bytes: number;
    categoria: CategoriaAdjunto;
    created_at: string;
    usuario: UsuarioTarea | null;
}

/** Evidencia de una tarea hija (RF-21/22), mostrada en la tarea padre para no reenviar archivos por fuera del sistema. */
export interface AdjuntoDeTareaHija extends AdjuntoTarea {
    tarea_hija_titulo: string;
}

export interface ChecklistPersonalItem {
    id: number;
    texto: string;
    completado: boolean;
    created_at: string;
}

export interface TareaDetalle {
    id: number;
    titulo: string;
    descripcion: string | null;
    estado: EstadoTarea;
    esta_atrasada: boolean;
    fecha_inicio: string | null;
    fecha_compromiso: string;
    fecha_cancelacion: string | null;
    motivo_cancelacion: string | null;
    created_at: string;
    responsable: UsuarioTarea;
    creador: UsuarioTarea;
    colaboradores: UsuarioTarea[];
    historial: HistorialEvento[];
    adjuntos: AdjuntoTarea[];
}

/** Rol del usuario que consulta respecto de esta tarea (RF-24 D4), coherente con las secciones de RF-09. */
export type RolUsuarioTarea = 'responsable' | 'colaborador' | 'delegado' | 'creador' | null;

export interface PermisosTarea {
    puedeReasignar: boolean;
    puedeAgregarColaborador: boolean;
    puedeCompletar: boolean;
    puedeRetroceder: boolean;
    puedeReportarProblema: boolean;
    puedeReportarNoParticipacion: boolean;
    puedeCancelar: boolean;
    puedeAdjuntar: boolean;
    puedeUsarChecklistPersonal: boolean;
}
