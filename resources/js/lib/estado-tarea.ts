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
 * Version atenuada del badge de atraso: se usa cuando la tarea YA esta
 * completada (se entrego tarde, pero ya esta resuelta) para que el check
 * verde de completada siga siendo el protagonista de la tarjeta en vez de
 * competir con el rojo de "Atrasada".
 */
export const ENTREGADA_CON_ATRASO_BADGE_CLASSES = 'border-gris-1/30 bg-gris-1/10 text-gris-1';

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
