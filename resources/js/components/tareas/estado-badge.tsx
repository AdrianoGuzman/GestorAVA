import {
    ATRASADA_BADGE_CLASSES,
    ESTADO_TAREA_BADGE_CLASSES,
    ESTADO_TAREA_LABELS,
    PRIORIDAD_TAREA_BADGE_CLASSES,
    PRIORIDAD_TAREA_LABELS,
} from '@/lib/estado-tarea';
import { cn } from '@/lib/utils';
import type { EstadoTarea, PrioridadTarea } from '@/types/tarea';
import type { EstadoProyecto } from '@/types/proyecto';

/**
 * RF-24 D2: el estado y el indicador "atrasada" se muestran a la vez cuando
 * corresponde -- no son excluyentes.
 */
export function EstadoBadge({ estado, className }: { estado: EstadoTarea; className?: string }) {
    return (
        <span className={cn('inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium', ESTADO_TAREA_BADGE_CLASSES[estado], className)}>
            {ESTADO_TAREA_LABELS[estado]}
        </span>
    );
}

export function AtrasadaBadge({ label = 'Atrasada', className }: { label?: string; className?: string }) {
    return (
        <span className={cn('inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium', ATRASADA_BADGE_CLASSES, className)}>
            {label}
        </span>
    );
}

/** Prioridad definida al crear la tarea (RF-04). Independiente del estado y de "atrasada" -- las tres senales pueden convivir en una misma tarjeta. */
export function PrioridadBadge({ prioridad, className }: { prioridad: PrioridadTarea; className?: string }) {
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium',
                PRIORIDAD_TAREA_BADGE_CLASSES[prioridad],
                className,
            )}
        >
            Prioridad {PRIORIDAD_TAREA_LABELS[prioridad].toLowerCase()}
        </span>
    );
}

const ESTADO_PROYECTO_BADGE_CLASSES: Record<EstadoProyecto, string> = {
    activo: 'border-verde-3 bg-verde-1 text-verde-6',
    cerrado: 'border-border bg-muted text-muted-foreground',
};

export function EstadoProyectoBadge({ estado, className }: { estado: EstadoProyecto; className?: string }) {
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium',
                ESTADO_PROYECTO_BADGE_CLASSES[estado],
                className,
            )}
        >
            {estado === 'activo' ? 'Activo' : 'Cerrado'}
        </span>
    );
}
