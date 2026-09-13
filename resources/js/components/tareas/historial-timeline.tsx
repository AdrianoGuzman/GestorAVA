import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import type { HistorialEvento, TipoEvento } from '@/types/tarea';
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
    checklist_item_creado: 'Agregó un ítem al checklist',
    checklist_item_marcado: 'Marcó un ítem del checklist',
    checklist_item_desmarcado: 'Desmarcó un ítem del checklist',
    checklist_item_editado: 'Editó un ítem del checklist',
    checklist_item_eliminado: 'Eliminó un ítem del checklist',
    adjunto_agregado: 'Adjuntó un archivo',
    tarea_atrasada: 'Se marcó como atrasada automáticamente',
    tarea_editada: 'Editó la tarea',
    tarea_proxima_a_vencer: 'Se avisó que la tarea está por vencer',
};

/** RF-24 D5 / RF-16: historial cronológico completo de eventos de la tarea. */
export function HistorialTimeline({ eventos }: { eventos: HistorialEvento[] }) {
    if (eventos.length === 0) {
        return <p className="text-sm text-muted-foreground">Todavía no hay eventos registrados.</p>;
    }

    return (
        <ol className="space-y-4 border-l border-gris-3 pl-4">
            {eventos.map((evento) => (
                <li key={evento.id} className="relative">
                    <span className={cn('absolute -left-[21px] top-1.5 size-2 rounded-full', COLOR_EVENTO[evento.tipo_evento])} />
                    <p className="text-sm font-medium text-foreground">
                        {evento.usuario?.name ?? 'Sistema'} — {ETIQUETAS_EVENTO[evento.tipo_evento]}
                    </p>
                    <p className="text-xs text-muted-foreground">{new Date(evento.created_at).toLocaleString('es-CL')}</p>
                    {typeof evento.datos_evento?.motivo === 'string' && (
                        <p className="mt-1 text-sm text-foreground">Motivo: {evento.datos_evento.motivo}</p>
                    )}
                </li>
            ))}
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
export function HistorialInline({ eventos }: { eventos: HistorialEvento[] }) {
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
                    <Input
                        type="date"
                        aria-label="Desde"
                        value={desde}
                        onChange={(e) => setDesde(e.target.value)}
                        className="h-8 w-36 text-xs"
                    />
                    <span className="text-xs text-muted-foreground">a</span>
                    <Input
                        type="date"
                        aria-label="Hasta"
                        value={hasta}
                        onChange={(e) => setHasta(e.target.value)}
                        className="h-8 w-36 text-xs"
                    />
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
                <HistorialTimeline eventos={eventosOrdenados} />
            </div>
        </div>
    );
}
