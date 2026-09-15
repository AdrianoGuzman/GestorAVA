import type { HistorialEventoProyecto, TipoEventoProyecto } from '@/types/proyecto';
import { History } from 'lucide-react';

const ETIQUETAS_EVENTO: Record<TipoEventoProyecto, string> = {
    creacion: 'Creó el proyecto',
    proyecto_editado: 'Editó el proyecto',
    seccion_creada: 'Agregó una sección',
    seccion_editada: 'Editó una sección',
};

const SIN_VALOR = '(vacío)';

function formatearTexto(valor: unknown, max = 60): string {
    if (valor === null || valor === undefined || valor === '') return SIN_VALOR;
    const texto = String(valor);
    return texto.length > max ? `${texto.slice(0, max)}…` : texto;
}

/** Los campos de fecha llegan como "yyyy-MM-dd" -- parsear con `new Date(valor)` directo
 *  corre el riesgo de correrse un día en zonas horarias negativas (UTC-3/4). */
function formatearFecha(valor: unknown): string {
    if (typeof valor !== 'string' || !valor) return SIN_VALOR;
    const [anio, mes, dia] = valor.slice(0, 10).split('-').map(Number);
    return new Date(anio, mes - 1, dia).toLocaleDateString('es-CL');
}

function formatearEstado(valor: unknown): string {
    return valor === 'cerrado' ? 'Cerrado' : valor === 'activo' ? 'Activo' : SIN_VALOR;
}

function formatearPeso(valor: unknown): string {
    return typeof valor === 'number' ? `${Math.round(valor * 100)}%` : SIN_VALOR;
}

interface DetalleEvento {
    label: string;
    anterior?: string;
    actual: string;
}

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

function construirDetalles(evento: HistorialEventoProyecto): DetalleEvento[] {
    const datos = evento.datos_evento;
    if (!datos) return [];

    switch (evento.tipo_evento) {
        case 'proyecto_editado': {
            const antes = datos.datos_anteriores as Record<string, unknown> | undefined;
            if (!antes) return [];
            return diffCampos(antes, datos, [
                { key: 'nombre', label: 'Nombre', formatear: (v) => formatearTexto(v, 60) },
                { key: 'descripcion', label: 'Descripción', formatear: (v) => formatearTexto(v, 50) },
                { key: 'estado', label: 'Estado', formatear: formatearEstado },
                { key: 'fecha_inicio', label: 'Fecha inicio', formatear: formatearFecha },
                { key: 'fecha_termino', label: 'Fecha término', formatear: formatearFecha },
            ]);
        }
        case 'seccion_editada': {
            const antes = datos.datos_anteriores as Record<string, unknown> | undefined;
            if (!antes) return [];
            return diffCampos(antes, datos, [
                { key: 'nombre', label: 'Nombre', formatear: (v) => formatearTexto(v, 60) },
                { key: 'peso', label: 'Peso', formatear: formatearPeso },
            ]);
        }
        case 'seccion_creada':
            return typeof datos.seccion_nombre === 'string'
                ? [{ label: 'Sección', actual: `${formatearTexto(datos.seccion_nombre, 60)} (peso ${formatearPeso(datos.peso)})` }]
                : [];
        case 'creacion':
            return [
                { label: 'Fecha inicio', actual: formatearFecha(datos.fecha_inicio) },
                { label: 'Fecha término', actual: formatearFecha(datos.fecha_termino) },
            ];
        default:
            return [];
    }
}

/** Trazabilidad del proyecto (14-09-2026, Franco): quién lo creó, qué cambió en cada edición, secciones agregadas/editadas -- mismo lenguaje visual que el historial de tareas. */
export function HistorialProyectoTimeline({ eventos }: { eventos: HistorialEventoProyecto[] }) {
    if (eventos.length === 0) {
        return <p className="text-sm text-muted-foreground">Todavía no hay eventos registrados.</p>;
    }

    const ordenados = [...eventos].sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());

    return (
        <div>
            <h3 className="mb-3 flex items-center gap-2 text-sm font-semibold text-foreground">
                <History className="size-4 text-verde-6" /> Actividad
                <span className="rounded-full bg-verde-2 px-1.5 py-0.5 text-xs font-semibold text-gris-2">{eventos.length}</span>
            </h3>
            <ol className="max-h-72 space-y-4 overflow-y-auto border-l border-gris-3 pr-1 pl-4">
                {ordenados.map((evento) => {
                    const detalles = construirDetalles(evento);
                    return (
                        <li key={evento.id} className="relative">
                            <span className="absolute -left-[21px] top-1.5 size-2 rounded-full bg-gris-1" />
                            <p className="text-sm font-medium text-foreground">
                                {evento.usuario?.name ?? 'Sistema'} — {ETIQUETAS_EVENTO[evento.tipo_evento]}
                            </p>
                            <p className="text-xs text-muted-foreground">{new Date(evento.created_at).toLocaleString('es-CL')}</p>
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
        </div>
    );
}
