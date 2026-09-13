import type { Persona } from '@/components/tareas/persona-picker';
import { Button } from '@/components/ui/button';
import { DatePickerButton, stringAFecha } from '@/components/ui/date-picker-button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PRIORIDAD_TAREA_LABELS } from '@/lib/estado-tarea';
import { cn } from '@/lib/utils';
import type { HistorialEvento, PrioridadTarea, TipoEvento } from '@/types/tarea';
import { History } from 'lucide-react';
import { useMemo, useState } from 'react';

/**
 * Color del punto de cada evento, usando solo los colores institucionales de
 * AVA (sin agregar amarillo/naranja): rojo para eventos negativos/de
 * problema, verde para eventos positivos/de cierre, gris para el resto
 * (informativos, sin carga positiva ni negativa).
 */
const COLOR_EVENTO: Record<TipoEvento, string> = {
    creacion: 'bg-verde-5',
    reasignacion: 'bg-gris-1',
    reasignacion_excepcional: 'bg-rojo-1',
    colaborador_agregado: 'bg-verde-5',
    transicion_automatica: 'bg-gris-1',
    completada: 'bg-verde-5',
    retroceso: 'bg-rojo-1',
    problema_reportado: 'bg-rojo-1',
    no_participacion_reportada: 'bg-rojo-1',
    cancelacion: 'bg-rojo-1',
    dependencia_creada: 'bg-gris-1',
    dependencia_resuelta: 'bg-verde-5',
    tarea_hija_creada: 'bg-gris-1',
    checklist_item_creado: 'bg-gris-1',
    checklist_item_marcado: 'bg-verde-5',
    checklist_item_desmarcado: 'bg-gris-1',
    checklist_item_editado: 'bg-gris-1',
    checklist_item_eliminado: 'bg-rojo-1',
    adjunto_agregado: 'bg-gris-1',
    tarea_atrasada: 'bg-rojo-1',
    tarea_editada: 'bg-gris-1',
    tarea_proxima_a_vencer: 'bg-gris-1',
};

const ETIQUETAS_EVENTO: Record<TipoEvento, string> = {
    creacion: 'Creó la tarea',
    reasignacion: 'Reasignó el responsable',
    reasignacion_excepcional: 'Reasignó el responsable (excepción)',
    colaborador_agregado: 'Agregó un colaborador',
    transicion_automatica: 'La tarea pasó a En progreso automáticamente',
    completada: 'Marcó la tarea como completada',
    retroceso: 'Retrocedió la tarea a Pendiente',
    problema_reportado: 'Reportó un problema en la tarea',
    no_participacion_reportada: 'Avisó que no puede seguir participando',
    cancelacion: 'Canceló la tarea',
    dependencia_creada: 'Creó una dependencia',
    dependencia_resuelta: 'Resolvió una dependencia',
    checklist_item_creado: 'Agregó una subtarea',
    checklist_item_marcado: 'Marcó una subtarea como hecha',
    checklist_item_desmarcado: 'Desmarcó una subtarea',
    checklist_item_editado: 'Editó una subtarea',
    checklist_item_eliminado: 'Eliminó una subtarea',
    tarea_hija_creada: 'Creó una tarea hija',
    adjunto_agregado: 'Adjuntó un archivo',
    tarea_atrasada: 'Se marcó como atrasada automáticamente',
    tarea_editada: 'Editó la tarea',
    tarea_proxima_a_vencer: 'Se avisó que la tarea está por vencer',
};

/**
 * Marcar/desmarcar una subtarea (checklist compartido, RF-23 -- la UI le
 * dice "Subtareas") queda igual registrado en la BD para auditoría, pero se
 * omite de esta línea de tiempo porque una subtarea que se marca y desmarca
 * varias veces la llena de ruido sin aportar nada que ya no muestre la
 * propia lista de subtareas.
 */
const EVENTOS_OCULTOS_EN_TIMELINE = new Set<TipoEvento>(['checklist_item_marcado', 'checklist_item_desmarcado']);

const SIN_VALOR = '(vacío)';

/** Recorta valores largos (sobre todo descripción) -- mostrar el texto completo de
 *  un antes/después en una línea de timeline la satura en vez de aclararla. */
function truncar(texto: string, max: number): string {
    return texto.length > max ? `${texto.slice(0, max)}…` : texto;
}

function formatearTexto(valor: unknown, max = 60): string {
    if (valor === null || valor === undefined || valor === '') return SIN_VALOR;
    return truncar(String(valor), max);
}

/** Los campos de fecha llegan como "yyyy-MM-dd" -- parsear con `new Date()` directo
 *  corre el riesgo de correrse un día en zonas horarias negativas (UTC-3/4). */
function formatearFecha(valor: unknown): string {
    if (typeof valor !== 'string' || !valor) return SIN_VALOR;
    return stringAFecha(valor.slice(0, 10)).toLocaleDateString('es-CL');
}

function formatearPrioridad(valor: unknown): string {
    return PRIORIDAD_TAREA_LABELS[valor as PrioridadTarea] ?? SIN_VALOR;
}

interface DetalleEvento {
    label: string;
    anterior?: string;
    actual: string;
}

/**
 * Compara "antes" contra "después" campo a campo y solo devuelve los que
 * realmente cambiaron -- mostrar los 5 campos de una edición de tarea aunque
 * solo se haya movido una fecha satura la línea de tiempo sin aportar nada
 * (la preocupación de Franco al pedir esto).
 */
function diffCampos(
    antes: Record<string, unknown>,
    despues: Record<string, unknown>,
    campos: { key: string; label: string; formatear: (valor: unknown) => string }[],
): DetalleEvento[] {
    return campos.flatMap(({ key, label, formatear }) => {
        if (antes[key] === despues[key]) return [];
        return [{ label, anterior: formatear(antes[key]), actual: formatear(despues[key]) }];
    });
}

/**
 * Detalle específico por tipo de evento -- la mayoría de los eventos ya
 * quedan claros con la etiqueta + el motivo (si tiene); esto solo agrega
 * valor donde el backend ya guarda datos que hoy no se ven en ningún lado
 * (ver TareaService::actualizar(), ChecklistService::editar(),
 * ReasignacionService::ejecutar()).
 */
function construirDetalles(evento: HistorialEvento, nombrePorId: Map<number, string>): DetalleEvento[] {
    const datos = evento.datos_evento;
    if (!datos) return [];

    const nombreDe = (id: unknown): string => (typeof id === 'number' ? (nombrePorId.get(id) ?? `Usuario #${id}`) : SIN_VALOR);

    switch (evento.tipo_evento) {
        case 'tarea_editada': {
            const antes = datos.datos_anteriores as Record<string, unknown> | undefined;
            if (!antes) return [];
            return diffCampos(antes, datos, [
                { key: 'titulo', label: 'Título', formatear: (v) => formatearTexto(v, 60) },
                { key: 'descripcion', label: 'Descripción', formatear: (v) => formatearTexto(v, 50) },
                { key: 'fecha_inicio', label: 'Fecha inicio', formatear: formatearFecha },
                { key: 'fecha_compromiso', label: 'Fecha término', formatear: formatearFecha },
                { key: 'prioridad', label: 'Prioridad', formatear: formatearPrioridad },
            ]);
        }
        case 'checklist_item_editado': {
            const antes = datos.datos_anteriores as Record<string, unknown> | undefined;
            if (!antes) return [];
            return diffCampos(antes, datos, [
                { key: 'texto', label: 'Texto', formatear: (v) => formatearTexto(v, 60) },
                { key: 'dueno_id', label: 'Dueño', formatear: (v) => (v === null ? 'Sin dueño' : nombreDe(v)) },
                { key: 'fecha_limite', label: 'Fecha límite', formatear: formatearFecha },
            ]);
        }
        case 'reasignacion':
        case 'reasignacion_excepcional': {
            const detalles: DetalleEvento[] = [
                { label: 'Responsable', anterior: nombreDe(datos.responsable_anterior_id), actual: nombreDe(datos.responsable_nuevo_id) },
            ];
            if (datos.mantuvo_como_colaborador === true) {
                detalles.push({ label: 'Responsable saliente', actual: 'quedó como colaborador' });
            }
            return detalles;
        }
        case 'colaborador_agregado':
            return [{ label: 'Colaborador agregado', actual: nombreDe(datos.colaborador_id) }];
        case 'tarea_hija_creada':
            return typeof datos.titulo === 'string' ? [{ label: 'Tarea hija', actual: datos.titulo }] : [];
        default:
            return [];
    }
}

/** RF-24 D5 / RF-16: historial cronológico completo de eventos de la tarea. */
export function HistorialTimeline({ eventos, usuarios }: { eventos: HistorialEvento[]; usuarios: Persona[] }) {
    const visibles = eventos.filter((evento) => !EVENTOS_OCULTOS_EN_TIMELINE.has(evento.tipo_evento));
    const nombrePorId = useMemo(() => new Map(usuarios.map((persona) => [persona.id, persona.name])), [usuarios]);

    if (visibles.length === 0) {
        return <p className="text-sm text-muted-foreground">Todavía no hay eventos registrados.</p>;
    }

    return (
        <ol className="space-y-4 border-l border-gris-3 pl-4">
            {visibles.map((evento) => {
                // reasignacion_excepcional guarda el motivo bajo "motivo_excepcion"
                // (para no confundirlo con el motivo de una reasignacion normal, que
                // no tiene), pero para mostrarlo es el mismo caso que el resto.
                const motivo = evento.datos_evento?.motivo ?? evento.datos_evento?.motivo_excepcion;
                const detalles = construirDetalles(evento, nombrePorId);

                return (
                    <li key={evento.id} className="relative">
                        <span className={cn('absolute -left-[21px] top-1.5 size-2 rounded-full', COLOR_EVENTO[evento.tipo_evento])} />
                        <p className="text-sm font-medium text-foreground">
                            {evento.usuario?.name ?? 'Sistema'} — {ETIQUETAS_EVENTO[evento.tipo_evento]}
                        </p>
                        <p className="text-xs text-muted-foreground">{new Date(evento.created_at).toLocaleString('es-CL')}</p>
                        {typeof motivo === 'string' && <p className="mt-1 text-sm text-foreground">Motivo: {motivo}</p>}
                        {detalles.length > 0 && (
                            <ul className="mt-1 space-y-0.5">
                                {detalles.map((detalle) => (
                                    <li key={detalle.label} className="text-xs text-muted-foreground">
                                        <span className="font-medium text-foreground/70">{detalle.label}:</span>{' '}
                                        {detalle.anterior ? `${detalle.anterior} → ${detalle.actual}` : detalle.actual}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </li>
                );
            })}
        </ol>
    );
}

/**
 * Historial como seccion "Actividad" al final del detalle, siempre visible
 * -- despues de varias vueltas probando esconderlo (cinta con clip-path,
 * panel escapandose del modal, Sheet lateral), el feedback fue el mismo
 * cada vez: la info se sentia escondida o competia por espacio propio.
 * La referencia de Asana resuelve esto integrando el historial como
 * actividad al pie del detalle en vez de en un panel aparte, asi que el
 * filtro de fecha y el orden quedan en una fila simple arriba del timeline,
 * sin overlay ni trigger.
 */
export function HistorialInline({ eventos, usuarios }: { eventos: HistorialEvento[]; usuarios: Persona[] }) {
    const [desde, setDesde] = useState('');
    const [hasta, setHasta] = useState('');
    const [orden, setOrden] = useState<'reciente' | 'antigua'>('reciente');

    const eventosFiltrados = useMemo(() => {
        if (!desde && !hasta) return eventos;

        return eventos.filter((evento) => {
            const fecha = evento.created_at.slice(0, 10);
            if (desde && fecha < desde) return false;
            if (hasta && fecha > hasta) return false;
            return true;
        });
    }, [eventos, desde, hasta]);

    const eventosOrdenados = useMemo(() => {
        const signo = orden === 'reciente' ? -1 : 1;
        return [...eventosFiltrados].sort(
            (a, b) => signo * (new Date(a.created_at).getTime() - new Date(b.created_at).getTime()),
        );
    }, [eventosFiltrados, orden]);

    const hayFiltro = !!desde || !!hasta;

    return (
        <div>
            <div className="flex flex-wrap items-center justify-between gap-3">
                <h3 className="flex items-center gap-2 text-sm font-semibold text-foreground">
                    <History className="size-4 text-verde-6" /> Actividad
                    {eventos.length > 0 && (
                        <span className="rounded-full bg-verde-2 px-1.5 py-0.5 text-xs font-semibold text-gris-2">{eventos.length}</span>
                    )}
                </h3>

                <div className="flex flex-wrap items-center gap-2">
                    <DatePickerButton label="Desde" valor={desde} onChange={setDesde} />
                    <span className="text-xs text-muted-foreground">a</span>
                    <DatePickerButton label="Hasta" valor={hasta} onChange={setHasta} />
                    {hayFiltro && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => {
                                setDesde('');
                                setHasta('');
                            }}
                        >
                            Limpiar
                        </Button>
                    )}
                    <Select value={orden} onValueChange={(valor) => setOrden(valor as 'reciente' | 'antigua')}>
                        <SelectTrigger className="h-8 w-44 text-xs">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="reciente">Más recientes primero</SelectItem>
                            <SelectItem value="antigua">Más antiguas primero</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            {hayFiltro && eventosFiltrados.length === 0 && (
                <p className="mt-3 text-xs text-muted-foreground">Sin eventos en ese rango de fechas.</p>
            )}

            {/* Alto fijo con scroll propio: sin esto, cada checklist marcado o
                reasignacion suma un evento mas y la seccion (y por lo tanto la
                pagina entera) crece sin limite. Con esto el detalle de la tarea
                siempre mide lo mismo, sin importar cuanta actividad acumule. */}
            <div className="mt-4 max-h-72 overflow-y-auto pr-1">
                <HistorialTimeline eventos={eventosOrdenados} usuarios={usuarios} />
            </div>
        </div>
    );
}
