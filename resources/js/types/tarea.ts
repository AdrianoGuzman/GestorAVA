export type EstadoTarea = 'pendiente' | 'en_progreso' | 'completada' | 'cancelada';

export type PrioridadTarea = 'alta' | 'media' | 'baja';

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
    | 'tarea_hija_creada'
    | 'adjunto_agregado'
    | 'tarea_atrasada'
    | 'tarea_editada'
    | 'tarea_proxima_a_vencer';

export interface UsuarioTarea {
    id: number;
    name: string;
    email: string;
    /** AVA Montajes (17-09-2026): qué nivel jerárquico maneja la tarea, visible junto al responsable. */
    nivel_jerarquico?: import('@/types/usuario').NivelJerarquico | null;
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

/** Checklist compartido (RF-23), visible solo cuando la tarea tiene colaboradores. */
export interface ChecklistItem {
    id: number;
    tarea_id: number;
    texto: string;
    completado: boolean;
    dueno_id: number | null;
    dueno: UsuarioTarea | null;
    fecha_limite: string | null;
    created_at: string;
}

/** Tarea hija (RF-21/22), mostrada en la sección Dependencias de la tarea padre. */
export interface TareaHija {
    id: number;
    codigo: string;
    titulo: string;
    estado: EstadoTarea;
    responsable: UsuarioTarea;
    fecha_compromiso: string;
    esta_atrasada: boolean;
}

/** Proyecto (agrupa tareas de varias unidades bajo una misma iniciativa estrategica). */
export interface ProyectoResumen {
    id: number;
    nombre: string;
    /** Acota las fechas de sus tareas (RN: toda tarea de un proyecto debe caer dentro de su plazo). */
    fecha_inicio: string | null;
    fecha_termino: string | null;
}

/** Sección dentro de un Proyecto (agrupa tareas por objetivo). */
export interface SeccionResumen {
    id: number;
    nombre: string;
    proyecto_id: number;
}

export interface TareaDetalle {
    id: number;
    titulo: string;
    descripcion: string | null;
    estado: EstadoTarea;
    prioridad: PrioridadTarea;
    esta_atrasada: boolean;
    fecha_inicio: string | null;
    fecha_compromiso: string;
    fecha_cancelacion: string | null;
    motivo_cancelacion: string | null;
    created_at: string;
    updated_at: string;
    responsable: UsuarioTarea;
    creador: UsuarioTarea;
    colaboradores: UsuarioTarea[];
    proyecto: ProyectoResumen | null;
    seccion: { id: number; nombre: string } | null;
    historial: HistorialEvento[];
    adjuntos: AdjuntoTarea[];
    checklist_items: ChecklistItem[];
    tareas_hijas: TareaHija[];
}

/** Rol del usuario que consulta respecto de esta tarea (RF-24 D4), coherente con las secciones de RF-09. */
export type RolUsuarioTarea = 'responsable' | 'colaborador' | 'delegado' | 'creador' | null;

/** Fila de tarea dentro de "Mis tareas" (RF-09) — no es el detalle completo, solo lo necesario para listar. */
export interface TareaResumen {
    id: number;
    codigo: string;
    titulo: string;
    estado: EstadoTarea;
    prioridad: PrioridadTarea;
    esta_atrasada: boolean;
    fecha_compromiso: string;
    updated_at: string;
    responsable: UsuarioTarea;
    unidad_organizacional: { id: number; nombre: string } | null;
    rol: NonNullable<RolUsuarioTarea>;
    /** Ultimo evento del historial (Idea D): quien toco la tarea por ultima vez, sin abrirla. */
    ultimo_evento: { tipo_evento: TipoEvento; usuario: UsuarioTarea | null; created_at: string } | null;
}

export interface ContadoresMisTareas {
    total: number;
    atrasadas: number;
    en_progreso: number;
    pendientes: number;
    completadas: number;
    prioridad_alta: number;
}

/** Filtro rapido por rol (RF-09): distinto del rol real de cada tarea, es el valor que viaja en la URL. */
export type FiltroRolMisTareas = 'responsable' | 'colaborador' | 'delegadas_por_mi' | 'creadas_por_mi';

export interface FiltrosMisTareas {
    busqueda?: string | null;
    estado?: EstadoTarea[];
    prioridad?: PrioridadTarea[];
    solo_atrasadas?: boolean;
    unidad_organizacional_id?: number | null;
    proyecto_id?: number | null;
    filtro_rol?: FiltroRolMisTareas;
}

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
    puedeUsarChecklist: boolean;
    puedeAsignarDuenoChecklist: boolean;
    puedeCrearTareaHija: boolean;
    puedeEditar: boolean;
}
