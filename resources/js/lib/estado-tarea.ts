import type { EstadoTarea, PrioridadTarea, RolUsuarioTarea } from '@/types/tarea';

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
    cancelada: 'Cancelada',
};

export const ESTADO_TAREA_BADGE_CLASSES: Record<EstadoTarea, string> = {
    pendiente: 'border-gris-1 bg-white text-gris-2',
    en_progreso: 'border-verde-3 bg-verde-2 text-gris-2',
    // completada=gris-2 se vuelve invisible en modo oscuro porque el fondo
    // de las tarjetas (.dark --card en app.css) es exactamente el mismo
    // color -- se aclara solo en modo oscuro, sin tocar el modo claro.
    completada: 'border-gris-2 bg-gris-2 text-white dark:border-gris-1 dark:bg-gris-1/25',
    cancelada: 'border-gris-1/30 bg-gris-1/10 text-gris-1',
};

export const ATRASADA_BADGE_CLASSES = 'border-rojo-1/30 bg-rojo-1/10 text-rojo-1';

/**
 * Prioridad definida por quien crea la tarea (alta/media/baja). Colores
 * distintos de "atrasada" (rojo, reservado para vencimiento) para no
 * confundir dos señales distintas -- alta usa naranjo, un tono de alerta
 * propio, y baja usa el mismo gris neutro que "cancelada"/"pendiente" para
 * no llamar la atencion.
 */
export const PRIORIDAD_TAREA_LABELS: Record<PrioridadTarea, string> = {
    alta: 'Alta',
    media: 'Media',
    baja: 'Baja',
};

export const PRIORIDAD_TAREA_BADGE_CLASSES: Record<PrioridadTarea, string> = {
    alta: 'border-naranjo-1/30 bg-naranjo-1/10 text-naranjo-1',
    media: 'border-amarillo-1/40 bg-amarillo-1/10 text-amarillo-1',
    baja: 'border-gris-1/30 bg-gris-1/10 text-gris-1',
};

/** Orden de mayor a menor urgencia, usado para listar los checkbox/pills de prioridad siempre en el mismo orden. */
export const PRIORIDADES_ORDENADAS: PrioridadTarea[] = ['alta', 'media', 'baja'];

/**
 * Version atenuada del badge de atraso: se usa cuando la tarea YA esta
 * completada (se entrego tarde, pero ya esta resuelta) para que el check
 * verde de completada siga siendo el protagonista de la tarjeta en vez de
 * competir con el rojo de "Atrasada".
 */
export const ENTREGADA_CON_ATRASO_BADGE_CLASSES = 'border-gris-1/30 bg-gris-1/10 text-gris-1';

/**
 * "YYYY-MM-DD" de mañana, en hora local -- fecha_compromiso debe ser
 * estrictamente a futuro (RF-04/RF-18, `after:today`), asi que hoy ya no es
 * una opcion valida. Se usa como `min` en los inputs de fecha de los
 * dialogos de crear/duplicar tarea.
 */
export function fechaMinimaCompromiso(): string {
    const manana = new Date();
    manana.setDate(manana.getDate() + 1);
    const anio = manana.getFullYear();
    const mes = String(manana.getMonth() + 1).padStart(2, '0');
    const dia = String(manana.getDate()).padStart(2, '0');
    return `${anio}-${mes}-${dia}`;
}

/**
 * Horas entre la medianoche de fecha_compromiso y el instante real en que se
 * completo la tarea (aprox. via updated_at, estable porque no se puede
 * editar una tarea ya completada). fecha_compromiso no tiene hora propia
 * -- no hay una "hora limite" real -- asi que esto es una aproximacion
 * cosmetica pensada solo para dar sensacion de magnitud del atraso, no un
 * calculo contra un limite horario configurado.
 */
export function calcularHorasAtrasoEntrega(fechaCompromiso: string, completadaEn: string): number {
    const compromiso = new Date(fechaCompromiso);
    compromiso.setHours(0, 0, 0, 0);
    const completada = new Date(completadaEn);

    return Math.max(0, Math.floor((completada.getTime() - compromiso.getTime()) / (1000 * 60 * 60)));
}

export function formatearDuracionAtraso(horasTotales: number): string {
    const dias = Math.floor(horasTotales / 24);
    const horas = horasTotales % 24;
    const partes: string[] = [];

    if (dias > 0) {
        partes.push(`${dias} ${dias === 1 ? 'día' : 'días'}`);
    }
    if (horas > 0 || partes.length === 0) {
        partes.push(`${horas} ${horas === 1 ? 'hora' : 'horas'}`);
    }

    return partes.join(' y ');
}

export const ROL_USUARIO_LABELS: Record<NonNullable<RolUsuarioTarea>, string> = {
    responsable: 'Responsable',
    colaborador: 'Colaborador',
    delegado: 'Delegó',
    creador: 'Creador',
};
