import { ATRASADA_BADGE_CLASSES, ESTADO_TAREA_BADGE_CLASSES, ESTADO_TAREA_LABELS } from '@/lib/estado-tarea';
import { cn } from '@/lib/utils';
import type { EstadoTarea } from '@/types/tarea';

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

export function AtrasadaBadge({ className }: { className?: string }) {
    return (
        <span className={cn('inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium', ATRASADA_BADGE_CLASSES, className)}>
            Atrasada
        </span>
    );
}
