import type { EstadoTarea, PrioridadTarea, UsuarioTarea } from '@/types/tarea';

export type EstadoProyecto = 'activo' | 'cerrado';

/** Fila de tarea dentro de "Mis proyectos" -- lo justo para listarla agrupada, no el detalle completo. */
export interface TareaDeProyecto {
    id: number;
    codigo: string;
    titulo: string;
    estado: EstadoTarea;
    prioridad: PrioridadTarea;
    esta_atrasada: boolean;
    responsable: UsuarioTarea;
}

export type TipoEventoProyecto = 'creacion' | 'proyecto_editado' | 'seccion_creada' | 'seccion_editada';

/** Evento de trazabilidad del proyecto (14-09-2026): quién lo creó, qué cambió en cada edición, secciones agregadas/editadas. */
export interface HistorialEventoProyecto {
    id: number;
    tipo_evento: TipoEventoProyecto;
    usuario: UsuarioTarea | null;
    datos_evento: Record<string, unknown> | null;
    created_at: string;
}

export interface ContadoresProyecto {
    total: number;
    atrasadas: number;
    en_progreso: number;
    pendientes: number;
    completadas: number;
    prioridad_alta: number;
}

/** Agrupa tareas de un Proyecto por objetivo, con un peso (0-1) que pondera el avance del proyecto. */
export interface Seccion {
    id: number;
    nombre: string;
    peso: number;
    tareas: TareaDeProyecto[];
    /** Avance simple (completadas / no-canceladas) de sus tareas -- proxy hasta que exista progreso ponderado por subtarea. */
    avance: number;
}

/**
 * Fila de "Mis proyectos" (listado) -- info basica, sin secciones/tareas
 * completas (Franco, 14-09-2026: con varias secciones eso ocupaba toda la
 * vista). El detalle completo esta en ProyectoDetalle, ver proyectos/show.tsx.
 */
export interface ProyectoResumen {
    id: number;
    nombre: string;
    descripcion: string | null;
    estado: EstadoProyecto;
    creador: UsuarioTarea;
    fecha_inicio: string | null;
    fecha_termino: string | null;
    /**
     * Promedio ponderado del avance de cada sección (peso 0-1). Sin
     * secciones todavía, cae al cálculo simple sobre todas las tareas.
     */
    avance: number;
}

/** Detalle completo de un Proyecto (proyectos/show.tsx): secciones, tareas sin sección. */
export interface ProyectoDetalle extends ProyectoResumen {
    secciones: Seccion[];
    /** Tareas del proyecto sin sección asignada -- visibles, pero fuera del cálculo ponderado. */
    tareasSinSeccion: TareaDeProyecto[];
    contadores: ContadoresProyecto;
    historial: HistorialEventoProyecto[];
}
