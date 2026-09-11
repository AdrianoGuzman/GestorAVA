import type { EstadoTarea, RolUsuarioTarea } from '@/types/tarea';

/**
 * Colores de estado, unicos para toda la app (badges, listados, RF-24).
 * En progreso usa verde como "estado activo" (uso explicitamente permitido
 * por el manual de marca AVA); el resto usa negro/gris para no volver el
 * verde predominante. Atrasada (RF-14/D2, indicador independiente del
 * estado) siempre en rojo, ver `ATRASADA_BADGE_CLASSES`.
 */
export const ESTADO_TAREA_LABELS: Record<EstadoTarea, string> = {
    pendiente: 'Pendiente',
    en_progreso: 'En progreso',
    completada: 'Completada',
    rechazada: 'Rechazada',
    cancelada: 'Cancelada',
};

export const ESTADO_TAREA_BADGE_CLASSES: Record<EstadoTarea, string> = {
    pendiente: 'border-gris-1 bg-white text-gris-2',
    en_progreso: 'border-verde-3 bg-verde-2 text-gris-2',
    completada: 'border-gris-2 bg-gris-2 text-white',
    rechazada: 'border-naranjo-1/30 bg-naranjo-1/10 text-naranjo-1',
    cancelada: 'border-gris-1/30 bg-gris-1/10 text-gris-1',
};

export const ATRASADA_BADGE_CLASSES = 'border-rojo-1/30 bg-rojo-1/10 text-rojo-1';

export const ROL_USUARIO_LABELS: Record<NonNullable<RolUsuarioTarea>, string> = {
    responsable: 'Responsable',
    colaborador: 'Colaborador',
    delegado: 'Delegó',
    creador: 'Creador',
};
