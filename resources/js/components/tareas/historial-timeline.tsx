import type { HistorialEvento, TipoEvento } from '@/types/tarea';

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
                    <span className="absolute -left-[21px] top-1.5 size-2 rounded-full bg-verde-5" />
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
