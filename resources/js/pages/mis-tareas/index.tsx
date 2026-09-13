import { CrearTareaDialog } from '@/components/tareas/crear-tarea-dialog';
import { AtrasadaBadge, EstadoBadge, PrioridadBadge } from '@/components/tareas/estado-badge';
import type { Persona } from '@/components/tareas/persona-picker';
import { TareaDetalleModal, useTareaDetalleModal } from '@/components/tareas/tarea-detalle-modal';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Popover, PopoverClose, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    ATRASADA_BADGE_CLASSES,
    calcularHorasAtrasoEntrega,
    ENTREGADA_CON_ATRASO_BADGE_CLASSES,
    ESTADO_TAREA_BADGE_CLASSES,
    formatearDuracionAtraso,
    PRIORIDAD_TAREA_BADGE_CLASSES,
    PRIORIDAD_TAREA_LABELS,
    PRIORIDADES_ORDENADAS,
    ROL_USUARIO_LABELS,
} from '@/lib/estado-tarea';
import { cn } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import type { EstadoTarea, FiltroRolMisTareas, FiltrosMisTareas, PrioridadTarea, TareaResumen } from '@/types/tarea';
import { Head, router } from '@inertiajs/react';
import { Calendar, CheckCircle2, ChevronLeft, ChevronRight, Gauge, ListFilter, ListTodo, Plus, Search, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';

interface Props {
    tareas: TareaResumen[];
    contadores: {
        total: number;
        atrasadas: number;
        en_progreso: number;
        pendientes: number;
        completadas: number;
        prioridad_alta: number;
    };
    filtros: FiltrosMisTareas;
    unidadesOrganizacionales: { id: number; nombre: string }[];
    usuarios: Persona[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Mis tareas', href: '/mis-tareas' }];

const ESTADOS: { value: EstadoTarea; label: string }[] = [
    { value: 'pendiente', label: 'Pendiente' },
    { value: 'en_progreso', label: 'En progreso' },
    { value: 'completada', label: 'Completada' },
    { value: 'cancelada', label: 'Cancelada' },
];

const FILTROS_RAPIDOS: { value: FiltroRolMisTareas; label: string }[] = [
    { value: 'responsable', label: ROL_USUARIO_LABELS.responsable },
    { value: 'colaborador', label: ROL_USUARIO_LABELS.colaborador },
    { value: 'creadas_por_mi', label: 'Creadas por mí' },
    { value: 'delegadas_por_mi', label: 'Delegadas' },
];

type Tab = 'lista' | 'calendario' | 'metricas';

const TABS: { value: Tab; label: string; icono: typeof ListTodo }[] = [
    { value: 'lista', label: 'Lista', icono: ListTodo },
    { value: 'calendario', label: 'Calendario', icono: Calendar },
    { value: 'metricas', label: 'Panel de métricas', icono: Gauge },
];

function esManana(fecha: string): boolean {
    const manana = new Date();
    manana.setDate(manana.getDate() + 1);
    const objetivo = new Date(fecha);
    return manana.toDateString() === objetivo.toDateString();
}

function formatearFecha(fecha: string): string {
    return new Date(fecha).toLocaleDateString('es-CL', { day: '2-digit', month: 'short' }).toUpperCase();
}

/**
 * Clave "YYYY-MM-DD" en hora local, tanto para las columnas del calendario
 * (armadas con aritmetica de fechas local) como para fecha_compromiso
 * (recortando el string en vez de parsearlo como Date) -- evita el corrimiento
 * de un dia que da `new Date(fechaCompromiso)` en zonas horarias negativas
 * (UTC-3/4) al comparar contra "hoy" en el navegador.
 */
function claveFecha(fecha: Date): string {
    const anio = fecha.getFullYear();
    const mes = String(fecha.getMonth() + 1).padStart(2, '0');
    const dia = String(fecha.getDate()).padStart(2, '0');
    return `${anio}-${mes}-${dia}`;
}

function claveFechaCompromiso(fechaCompromiso: string): string {
    return fechaCompromiso.slice(0, 10);
}

function obtenerLunes(fecha: Date): Date {
    const lunes = new Date(fecha);
    const diasDesdeLunes = (lunes.getDay() + 6) % 7;
    lunes.setDate(lunes.getDate() - diasDesdeLunes);
    lunes.setHours(0, 0, 0, 0);
    return lunes;
}

function sumarDias(fecha: Date, dias: number): Date {
    const resultado = new Date(fecha);
    resultado.setDate(resultado.getDate() + dias);
    return resultado;
}

function primerDiaDelMes(fecha: Date): Date {
    return new Date(fecha.getFullYear(), fecha.getMonth(), 1);
}

/**
 * Grilla del mes en semanas completas: incluye los dias de fin del mes
 * anterior y de inicio del mes siguiente que hacen falta para completar la
 * primera y ultima semana (siempre Lun-Dom), para que el calendario nunca
 * corte una semana a la mitad.
 */
function generarGrillaMes(primerDiaMes: Date): Date[] {
    const inicio = obtenerLunes(primerDiaMes);
    const ultimoDiaMes = new Date(primerDiaMes.getFullYear(), primerDiaMes.getMonth() + 1, 0);
    const fin = sumarDias(obtenerLunes(ultimoDiaMes), 6);

    const dias: Date[] = [];
    for (let cursor = inicio; cursor <= fin; cursor = sumarDias(cursor, 1)) {
        dias.push(cursor);
    }
    return dias;
}

function capitalizar(texto: string): string {
    return texto.charAt(0).toUpperCase() + texto.slice(1);
}

function claseBarraTarea(tarea: TareaResumen): string {
    if (tarea.estado === 'completada') {
        return ESTADO_TAREA_BADGE_CLASSES.completada;
    }
    if (tarea.esta_atrasada) {
        return ATRASADA_BADGE_CLASSES;
    }
    return ESTADO_TAREA_BADGE_CLASSES[tarea.estado];
}

const ETIQUETAS_DIAS_SEMANA = ['LUN', 'MAR', 'MIÉ', 'JUE', 'VIE', 'SÁB', 'DOM'];

const MAX_TAREAS_VISIBLES_MES = 3;
const MAX_TAREAS_VISIBLES_SEMANA = 6;

type VistaCalendario = 'dia' | 'semana' | 'mes' | 'anio';

const VISTAS_CALENDARIO: { value: VistaCalendario; label: string }[] = [
    { value: 'dia', label: 'Día' },
    { value: 'semana', label: 'Semana' },
    { value: 'mes', label: 'Mes' },
    { value: 'anio', label: 'Año' },
];

function DiaPopoverContenido({ dia, tareas, onAbrirTarea }: { dia: Date; tareas: TareaResumen[]; onAbrirTarea: (id: number) => void }) {
    return (
        <>
            <div className="flex items-start justify-between">
                <div>
                    <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                        {dia.toLocaleDateString('es-CL', { weekday: 'short' }).replace('.', '')}
                    </p>
                    <p className="text-3xl font-bold text-foreground">{dia.getDate()}</p>
                </div>
                <PopoverClose className="rounded-sm p-1 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground">
                    <X className="size-4" />
                </PopoverClose>
            </div>

            {tareas.length === 0 ? (
                <p className="text-sm text-muted-foreground">No hay tareas registradas este día.</p>
            ) : (
                <div className="space-y-1.5">
                    {tareas.map((tarea) => (
                        <TareaBarra key={tarea.id} tarea={tarea} atenuada={false} onAbrir={onAbrirTarea} />
                    ))}
                </div>
            )}
        </>
    );
}

/**
 * Toda la celda de un dia es clickeable. Si tiene tareas, abre el popover
 * con el detalle del dia. Si esta vacia y es estrictamente a futuro, abre
 * directo el dialog de "Nueva tarea" con la fecha precargada (no tiene
 * sentido mostrar un popover vacio ahi); un dia de hoy o pasado y vacio no
 * puede agendar nada nuevo (fecha_compromiso exige a futuro, RF-04), asi que
 * ahi si muestra el popover informativo en vez de abrir un formulario que
 * el backend va a rechazar.
 */
function CeldaCalendario({
    dia,
    tareas,
    hoyClave,
    onAbrirTarea,
    onCrearEnFecha,
    className,
    children,
    compacta = false,
}: {
    dia: Date;
    tareas: TareaResumen[];
    hoyClave: string;
    onAbrirTarea: (id: number) => void;
    onCrearEnFecha: (dia: Date) => void;
    className?: string;
    children: React.ReactNode;
    /** Año usa celdas muy chicas: sin espacio para el "+" que insinúa "crear tarea acá". */
    compacta?: boolean;
}) {
    // fecha_compromiso exige estrictamente a futuro (RF-04, after:today) --
    // hoy tampoco es una fecha valida para agendar, igual que el pasado.
    const puedeCrearAqui = claveFecha(dia) > hoyClave;

    if (tareas.length === 0 && puedeCrearAqui) {
        return (
            <div
                role="button"
                tabIndex={0}
                onClick={() => onCrearEnFecha(dia)}
                onKeyDown={(e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        onCrearEnFecha(dia);
                    }
                }}
                className={cn(
                    'group relative cursor-pointer text-left transition-colors hover:border-verde-5 hover:bg-verde-1/30',
                    className,
                )}
            >
                {children}
                {!compacta && (
                    <Plus className="pointer-events-none absolute right-2 bottom-2 size-4 text-verde-6 opacity-0 transition-opacity group-hover:opacity-100" />
                )}
            </div>
        );
    }

    return (
        <Popover>
            <PopoverTrigger asChild>
                <div
                    role="button"
                    tabIndex={0}
                    className={cn('cursor-pointer text-left transition-colors hover:border-verde-5 hover:bg-muted/40', className)}
                >
                    {children}
                </div>
            </PopoverTrigger>
            <PopoverContent className="w-64 space-y-3 p-4" align="start">
                <DiaPopoverContenido dia={dia} tareas={tareas} onAbrirTarea={onAbrirTarea} />
            </PopoverContent>
        </Popover>
    );
}

function TareaBarra({
    tarea,
    atenuada,
    onAbrir,
}: {
    tarea: TareaResumen;
    atenuada: boolean;
    onAbrir: (id: number) => void;
}) {
    return (
        <button
            type="button"
            onClick={(e) => {
                e.stopPropagation();
                onAbrir(tarea.id);
            }}
            className={cn(
                'block w-full truncate rounded-md border px-2 py-1 text-left text-xs font-medium transition-opacity hover:opacity-80',
                atenuada && 'opacity-60',
                claseBarraTarea(tarea),
            )}
        >
            {tarea.titulo}
        </button>
    );
}

function VistaMes({
    mesAncla,
    tareasPorDia,
    hoyClave,
    onAbrirTarea,
    onCrearEnFecha,
}: {
    mesAncla: Date;
    tareasPorDia: Map<string, TareaResumen[]>;
    hoyClave: string;
    onAbrirTarea: (id: number) => void;
    onCrearEnFecha: (dia: Date) => void;
}) {
    const dias = useMemo(() => generarGrillaMes(mesAncla), [mesAncla]);

    return (
        <div className="overflow-x-auto">
            <div className="min-w-[840px]">
                <div className="grid grid-cols-7 gap-2 px-1 pb-1">
                    {ETIQUETAS_DIAS_SEMANA.map((etiqueta) => (
                        <p key={etiqueta} className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                            {etiqueta}
                        </p>
                    ))}
                </div>

                <div className="grid grid-cols-7 gap-2">
                    {dias.map((dia) => {
                        const clave = claveFecha(dia);
                        const esHoy = clave === hoyClave;
                        const esDelMesActual = dia.getMonth() === mesAncla.getMonth();
                        const tareasDelDia = tareasPorDia.get(clave) ?? [];
                        const tareasVisibles = tareasDelDia.slice(0, MAX_TAREAS_VISIBLES_MES);
                        const tareasOcultas = tareasDelDia.length - tareasVisibles.length;

                        return (
                            <CeldaCalendario
                                key={clave}
                                dia={dia}
                                tareas={tareasDelDia}
                                hoyClave={hoyClave}
                                onAbrirTarea={onAbrirTarea}
                                onCrearEnFecha={onCrearEnFecha}
                                className={cn(
                                    'block min-h-24 w-full space-y-1.5 rounded-lg border p-1.5',
                                    esDelMesActual ? 'border-border bg-muted/20' : 'border-border/50 bg-muted/5',
                                )}
                            >
                                <div className="flex items-center gap-1">
                                    <span
                                        className={cn(
                                            'flex size-6 items-center justify-center rounded-full text-sm font-semibold',
                                            esHoy ? 'bg-verde-5 text-gris-2' : esDelMesActual ? 'text-foreground' : 'text-muted-foreground',
                                        )}
                                    >
                                        {dia.getDate()}
                                    </span>
                                    {!esDelMesActual && (
                                        <span className="text-[10px] font-medium tracking-wide text-muted-foreground uppercase">
                                            {dia.toLocaleDateString('es-CL', { month: 'short' }).replace('.', '')}
                                        </span>
                                    )}
                                </div>

                                {tareasVisibles.map((tarea) => (
                                    <TareaBarra key={tarea.id} tarea={tarea} atenuada={!esDelMesActual} onAbrir={onAbrirTarea} />
                                ))}

                                {tareasOcultas > 0 && <p className="px-2 text-xs font-medium text-verde-6">+{tareasOcultas} más</p>}
                            </CeldaCalendario>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}

function VistaSemana({
    lunes,
    tareasPorDia,
    hoyClave,
    onAbrirTarea,
    onCrearEnFecha,
}: {
    lunes: Date;
    tareasPorDia: Map<string, TareaResumen[]>;
    hoyClave: string;
    onAbrirTarea: (id: number) => void;
    onCrearEnFecha: (dia: Date) => void;
}) {
    const dias = useMemo(() => Array.from({ length: 7 }, (_, i) => sumarDias(lunes, i)), [lunes]);

    return (
        <div className="overflow-x-auto">
            <div className="grid min-w-[840px] grid-cols-7 gap-2">
                {dias.map((dia) => {
                    const clave = claveFecha(dia);
                    const esHoy = clave === hoyClave;
                    const esPrimerDiaDelMes = dia.getDate() === 1;
                    const tareasDelDia = tareasPorDia.get(clave) ?? [];
                    const tareasVisibles = tareasDelDia.slice(0, MAX_TAREAS_VISIBLES_SEMANA);
                    const tareasOcultas = tareasDelDia.length - tareasVisibles.length;

                    return (
                        <CeldaCalendario
                            key={clave}
                            dia={dia}
                            tareas={tareasDelDia}
                            hoyClave={hoyClave}
                            onAbrirTarea={onAbrirTarea}
                            onCrearEnFecha={onCrearEnFecha}
                            className="block min-h-32 w-full space-y-2 rounded-lg border border-border bg-muted/20 p-1.5"
                        >
                            <div className="flex items-center justify-between px-0.5">
                                <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                    {dia.toLocaleDateString('es-CL', { weekday: 'short' }).replace('.', '')}
                                </p>
                                <div className="flex items-center gap-1">
                                    <span
                                        className={cn(
                                            'flex size-6 items-center justify-center rounded-full text-sm font-semibold',
                                            esHoy ? 'bg-verde-5 text-gris-2' : 'text-foreground',
                                        )}
                                    >
                                        {dia.getDate()}
                                    </span>
                                    {esPrimerDiaDelMes && (
                                        <span className="text-[10px] font-medium tracking-wide text-muted-foreground uppercase">
                                            {dia.toLocaleDateString('es-CL', { month: 'short' }).replace('.', '')}
                                        </span>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                {tareasVisibles.map((tarea) => (
                                    <TareaBarra key={tarea.id} tarea={tarea} atenuada={false} onAbrir={onAbrirTarea} />
                                ))}
                                {tareasOcultas > 0 && <p className="px-0.5 text-xs font-medium text-verde-6">+{tareasOcultas} más</p>}
                            </div>
                        </CeldaCalendario>
                    );
                })}
            </div>
        </div>
    );
}

function VistaDia({
    dia,
    tareasPorDia,
    hoyClave,
    onAbrirTarea,
    onCrearEnFecha,
}: {
    dia: Date;
    tareasPorDia: Map<string, TareaResumen[]>;
    hoyClave: string;
    onAbrirTarea: (id: number) => void;
    onCrearEnFecha: (dia: Date) => void;
}) {
    const tareasDelDia = tareasPorDia.get(claveFecha(dia)) ?? [];

    if (tareasDelDia.length === 0) {
        const puedeCrearAqui = claveFecha(dia) > hoyClave;

        return (
            <div className="space-y-3 py-8 text-center">
                <p className="text-sm text-muted-foreground">No hay tareas para este día.</p>
                {puedeCrearAqui && (
                    <Button variant="outline" size="sm" onClick={() => onCrearEnFecha(dia)}>
                        <Plus /> Nueva tarea
                    </Button>
                )}
            </div>
        );
    }

    return (
        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            {tareasDelDia.map((tarea) => (
                <TareaCard key={tarea.id} tarea={tarea} onAbrir={onAbrirTarea} />
            ))}
        </div>
    );
}

function MiniMes({
    mes,
    tareasPorDia,
    hoyClave,
    onAbrirTarea,
    onCrearEnFecha,
}: {
    mes: Date;
    tareasPorDia: Map<string, TareaResumen[]>;
    hoyClave: string;
    onAbrirTarea: (id: number) => void;
    onCrearEnFecha: (dia: Date) => void;
}) {
    const dias = useMemo(() => generarGrillaMes(mes), [mes]);

    return (
        <div className="space-y-2">
            <p className="text-sm font-semibold text-foreground">{capitalizar(mes.toLocaleDateString('es-CL', { month: 'long' }))}</p>
            <div className="grid grid-cols-7 gap-y-1">
                {ETIQUETAS_DIAS_SEMANA.map((etiqueta) => (
                    <p key={etiqueta} className="text-center text-[10px] font-medium text-muted-foreground">
                        {etiqueta[0]}
                    </p>
                ))}
                {dias.map((dia) => {
                    const clave = claveFecha(dia);
                    const esDelMesActual = dia.getMonth() === mes.getMonth();
                    const esHoy = clave === hoyClave;
                    const tareasDelDia = esDelMesActual ? (tareasPorDia.get(clave) ?? []) : [];
                    const tieneTareas = tareasDelDia.length > 0;

                    return (
                        <CeldaCalendario
                            key={clave}
                            dia={dia}
                            tareas={tareasDelDia}
                            hoyClave={hoyClave}
                            onAbrirTarea={onAbrirTarea}
                            onCrearEnFecha={onCrearEnFecha}
                            compacta
                            className="flex w-full flex-col items-center gap-0.5 rounded border border-transparent py-0.5"
                        >
                            <span
                                className={cn(
                                    'flex size-5 items-center justify-center rounded-full text-[11px]',
                                    esHoy ? 'bg-verde-5 font-semibold text-gris-2' : esDelMesActual ? 'text-foreground' : 'text-muted-foreground/50',
                                )}
                            >
                                {dia.getDate()}
                            </span>
                            <span className={cn('size-1 rounded-full', tieneTareas ? 'bg-verde-6' : 'bg-transparent')} />
                        </CeldaCalendario>
                    );
                })}
            </div>
        </div>
    );
}

function VistaAnio({
    anio,
    tareasPorDia,
    hoyClave,
    onAbrirTarea,
    onCrearEnFecha,
}: {
    anio: number;
    tareasPorDia: Map<string, TareaResumen[]>;
    hoyClave: string;
    onAbrirTarea: (id: number) => void;
    onCrearEnFecha: (dia: Date) => void;
}) {
    const meses = useMemo(() => Array.from({ length: 12 }, (_, i) => new Date(anio, i, 1)), [anio]);

    return (
        <div className="grid grid-cols-2 gap-x-6 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
            {meses.map((mes) => (
                <MiniMes
                    key={mes.getMonth()}
                    mes={mes}
                    tareasPorDia={tareasPorDia}
                    hoyClave={hoyClave}
                    onAbrirTarea={onAbrirTarea}
                    onCrearEnFecha={onCrearEnFecha}
                />
            ))}
        </div>
    );
}

function etiquetaSemana(lunes: Date): string {
    const domingo = sumarDias(lunes, 6);
    const mismoMes = lunes.getMonth() === domingo.getMonth();

    if (mismoMes) {
        return `${lunes.getDate()}–${domingo.getDate()} de ${capitalizar(domingo.toLocaleDateString('es-CL', { month: 'long' }))} ${domingo.getFullYear()}`;
    }

    const inicio = capitalizar(lunes.toLocaleDateString('es-CL', { day: 'numeric', month: 'short' })).replace('.', '');
    const fin = capitalizar(domingo.toLocaleDateString('es-CL', { day: 'numeric', month: 'short' })).replace('.', '');
    return `${inicio} – ${fin} ${domingo.getFullYear()}`;
}

function TabCalendario({ tareas, usuarios, onAbrirTarea }: { tareas: TareaResumen[]; usuarios: Persona[]; onAbrirTarea: (id: number) => void }) {
    const [cursor, setCursor] = useState(() => new Date());
    const [vista, setVista] = useState<VistaCalendario>('mes');
    const [direccion, setDireccion] = useState<-1 | 0 | 1>(0);
    const [fechaNuevaTarea, setFechaNuevaTarea] = useState<Date | null>(null);

    const hoyClave = claveFecha(new Date());

    const tareasPorDia = useMemo(() => {
        const mapa = new Map<string, TareaResumen[]>();
        for (const tarea of tareas) {
            const clave = claveFechaCompromiso(tarea.fecha_compromiso);
            const lista = mapa.get(clave) ?? [];
            lista.push(tarea);
            mapa.set(clave, lista);
        }
        return mapa;
    }, [tareas]);

    const irAnterior = () => {
        setDireccion(-1);
        setCursor((actual) => {
            if (vista === 'dia') return sumarDias(actual, -1);
            if (vista === 'semana') return sumarDias(actual, -7);
            if (vista === 'anio') return new Date(actual.getFullYear() - 1, actual.getMonth(), 1);
            return new Date(actual.getFullYear(), actual.getMonth() - 1, 1);
        });
    };

    const irSiguiente = () => {
        setDireccion(1);
        setCursor((actual) => {
            if (vista === 'dia') return sumarDias(actual, 1);
            if (vista === 'semana') return sumarDias(actual, 7);
            if (vista === 'anio') return new Date(actual.getFullYear() + 1, actual.getMonth(), 1);
            return new Date(actual.getFullYear(), actual.getMonth() + 1, 1);
        });
    };

    const irHoy = () => {
        setDireccion(0);
        setCursor(new Date());
    };

    const cambiarVista = (nuevaVista: VistaCalendario) => {
        setDireccion(0);
        setVista(nuevaVista);
    };

    const onCrearEnFecha = (dia: Date) => setFechaNuevaTarea(dia);

    const etiqueta = useMemo(() => {
        if (vista === 'dia') {
            return capitalizar(cursor.toLocaleDateString('es-CL', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));
        }
        if (vista === 'semana') {
            return etiquetaSemana(obtenerLunes(cursor));
        }
        if (vista === 'anio') {
            return String(cursor.getFullYear());
        }
        return capitalizar(cursor.toLocaleDateString('es-CL', { month: 'long', year: 'numeric' }));
    }, [cursor, vista]);

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="flex items-center gap-1">
                    <Button variant="outline" size="sm" onClick={irHoy}>
                        Hoy
                    </Button>
                    <Button variant="outline" size="icon" onClick={irAnterior}>
                        <ChevronLeft />
                    </Button>
                    <Button variant="outline" size="icon" onClick={irSiguiente}>
                        <ChevronRight />
                    </Button>
                    <p className="ml-2 text-sm font-semibold text-foreground">{etiqueta}</p>
                </div>

                <Select value={vista} onValueChange={(valor) => cambiarVista(valor as VistaCalendario)}>
                    <SelectTrigger className="w-28">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {VISTAS_CALENDARIO.map((v) => (
                            <SelectItem key={v.value} value={v.value}>
                                {v.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div
                key={`${vista}-${claveFecha(cursor)}`}
                className={cn(
                    'animate-in fade-in-0 duration-300',
                    direccion === 1 && 'slide-in-from-right-4',
                    direccion === -1 && 'slide-in-from-left-4',
                )}
            >
                {vista === 'mes' && (
                    <VistaMes
                        mesAncla={primerDiaDelMes(cursor)}
                        tareasPorDia={tareasPorDia}
                        hoyClave={hoyClave}
                        onAbrirTarea={onAbrirTarea}
                        onCrearEnFecha={onCrearEnFecha}
                    />
                )}
                {vista === 'semana' && (
                    <VistaSemana
                        lunes={obtenerLunes(cursor)}
                        tareasPorDia={tareasPorDia}
                        hoyClave={hoyClave}
                        onAbrirTarea={onAbrirTarea}
                        onCrearEnFecha={onCrearEnFecha}
                    />
                )}
                {vista === 'dia' && (
                    <VistaDia dia={cursor} tareasPorDia={tareasPorDia} hoyClave={hoyClave} onAbrirTarea={onAbrirTarea} onCrearEnFecha={onCrearEnFecha} />
                )}
                {vista === 'anio' && (
                    <VistaAnio
                        anio={cursor.getFullYear()}
                        tareasPorDia={tareasPorDia}
                        hoyClave={hoyClave}
                        onAbrirTarea={onAbrirTarea}
                        onCrearEnFecha={onCrearEnFecha}
                    />
                )}
            </div>

            <CrearTareaDialog
                personas={usuarios}
                open={fechaNuevaTarea !== null}
                onOpenChange={(open) => {
                    if (!open) setFechaNuevaTarea(null);
                }}
                fechaCompromisoInicial={fechaNuevaTarea ? claveFecha(fechaNuevaTarea) : undefined}
            />
        </div>
    );
}

function actualizarFiltros(filtros: FiltrosMisTareas, cambios: Partial<FiltrosMisTareas>) {
    router.get(
        route('mis-tareas.index'),
        { ...filtros, ...cambios },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function TareaCard({ tarea, onAbrir }: { tarea: TareaResumen; onAbrir: (id: number) => void }) {
    const completada = tarea.estado === 'completada';
    const entregadaConAtraso = completada && tarea.esta_atrasada;
    const mostrarVencimiento = !completada && (tarea.esta_atrasada || esManana(tarea.fecha_compromiso));

    return (
        <button
            type="button"
            onClick={() => onAbrir(tarea.id)}
            className={cn(
                'block w-full rounded-lg border border-border p-4 text-left transition-colors hover:border-verde-5 hover:bg-muted/30',
                completada && 'border-border/60 bg-muted/20',
            )}
        >
            <div className="flex flex-wrap items-start justify-between gap-2">
                <div className="min-w-0">
                    <p className="text-xs text-muted-foreground">{tarea.codigo}</p>
                    <p className={cn('flex items-center gap-2 font-semibold', completada ? 'text-muted-foreground' : 'text-foreground')}>
                        {completada && <CheckCircle2 className="size-5 shrink-0 text-verde-6" />}
                        <span className="truncate">{tarea.titulo}</span>
                    </p>
                </div>
                {entregadaConAtraso && (
                    <AtrasadaBadge
                        label={`${formatearDuracionAtraso(calcularHorasAtrasoEntrega(tarea.fecha_compromiso, tarea.updated_at))} de atraso`}
                        className={ENTREGADA_CON_ATRASO_BADGE_CLASSES}
                    />
                )}
                {mostrarVencimiento && <AtrasadaBadge className={tarea.esta_atrasada ? undefined : 'border-gris-1/30 bg-gris-1/10 text-gris-1'} />}
            </div>

            <div className="mt-2 space-y-0.5 text-sm text-muted-foreground">
                <p>
                    {tarea.responsable.name} · {ROL_USUARIO_LABELS[tarea.rol]}
                </p>
                <p>Vence: {formatearFecha(tarea.fecha_compromiso)}</p>
            </div>

            <div className="mt-3 flex flex-wrap items-center gap-1.5">
                <EstadoBadge estado={tarea.estado} />
                <PrioridadBadge prioridad={tarea.prioridad} />
            </div>
        </button>
    );
}

function ResumenContadores({ contadores }: { contadores: Props['contadores'] }) {
    return (
        <div className="flex flex-wrap items-center gap-x-6 gap-y-2 rounded-lg border border-border bg-muted/30 px-5 py-4">
            <span className="text-lg font-bold text-foreground">
                {contadores.total} {contadores.total === 1 ? 'tarea' : 'tareas'} en total
            </span>
            {contadores.atrasadas > 0 && (
                <span className="flex items-center gap-2 text-sm font-medium text-rojo-1">
                    <span className="size-2.5 rounded-full bg-rojo-1" /> {contadores.atrasadas} atrasadas
                </span>
            )}
            {contadores.prioridad_alta > 0 && (
                <span className="flex items-center gap-2 text-sm font-medium text-naranjo-1">
                    <span className="size-2.5 rounded-full bg-naranjo-1" /> {contadores.prioridad_alta} de prioridad alta
                </span>
            )}
            <span className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                <span className="size-2.5 rounded-full bg-verde-5" /> {contadores.en_progreso} en progreso
            </span>
            <span className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                <span className="size-2.5 rounded-full bg-gris-1" /> {contadores.pendientes} pendientes
            </span>
            <span className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                <span className="size-2.5 rounded-full bg-foreground" /> {contadores.completadas} completadas
            </span>
        </div>
    );
}

function TabLista({ tareas, contadores, filtros, unidadesOrganizacionales, usuarios, onAbrirTarea }: Props & { onAbrirTarea: (id: number) => void }) {
    const [busqueda, setBusqueda] = useState(filtros.busqueda ?? '');
    const primerRender = useRef(true);

    useEffect(() => {
        if (primerRender.current) {
            primerRender.current = false;
            return;
        }

        const id = setTimeout(() => actualizarFiltros(filtros, { busqueda: busqueda || null }), 400);
        return () => clearTimeout(id);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [busqueda]);

    const estadosSeleccionados = filtros.estado ?? [];
    const prioridadesSeleccionadas = filtros.prioridad ?? [];
    const filtrosActivos =
        estadosSeleccionados.length > 0 ||
        prioridadesSeleccionadas.length > 0 ||
        !!filtros.solo_atrasadas ||
        !!filtros.unidad_organizacional_id;

    const alternarEstado = (estado: EstadoTarea) => {
        const siguiente = estadosSeleccionados.includes(estado)
            ? estadosSeleccionados.filter((e) => e !== estado)
            : [...estadosSeleccionados, estado];
        actualizarFiltros(filtros, { estado: siguiente });
    };

    const alternarPrioridad = (prioridad: PrioridadTarea) => {
        const siguiente = prioridadesSeleccionadas.includes(prioridad)
            ? prioridadesSeleccionadas.filter((p) => p !== prioridad)
            : [...prioridadesSeleccionadas, prioridad];
        actualizarFiltros(filtros, { prioridad: siguiente });
    };

    const alternarFiltroRapido = (rol: FiltroRolMisTareas) => {
        actualizarFiltros(filtros, { filtro_rol: filtros.filtro_rol === rol ? undefined : rol });
    };

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <h2 className="text-lg font-semibold text-foreground">Tareas</h2>
                <CrearTareaDialog
                    personas={usuarios}
                    trigger={
                        <Button size="sm" className="bg-verde-5 text-gris-2 hover:bg-verde-6">
                            <Plus /> Nueva tarea
                        </Button>
                    }
                />
            </div>

            <div className="flex flex-wrap gap-2">
                <div className="relative min-w-56 flex-1">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        value={busqueda}
                        onChange={(e) => setBusqueda(e.target.value)}
                        placeholder="Buscar por nombre o código (TAR-0001)..."
                        className="pl-9"
                    />
                </div>

                <Popover>
                    <PopoverTrigger asChild>
                        <Button variant="outline" size="sm" className={cn(filtrosActivos && 'border-verde-5 text-verde-6')}>
                            <ListFilter /> Varios filtros
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent className="w-80 space-y-5 p-4" align="end">
                        <div className="flex items-center justify-between">
                            <p className="text-sm font-semibold text-foreground">Filtros</p>
                            {filtrosActivos && (
                                <button
                                    type="button"
                                    onClick={() =>
                                        actualizarFiltros(filtros, {
                                            estado: [],
                                            prioridad: [],
                                            solo_atrasadas: false,
                                            unidad_organizacional_id: null,
                                        })
                                    }
                                    className="text-xs font-medium text-verde-6 hover:underline"
                                >
                                    Limpiar filtros
                                </button>
                            )}
                        </div>

                        <div className="space-y-2">
                            <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Estado</p>
                            <div className="flex flex-wrap gap-1.5">
                                {ESTADOS.map((estado) => {
                                    const activo = estadosSeleccionados.includes(estado.value);
                                    return (
                                        <button
                                            key={estado.value}
                                            type="button"
                                            onClick={() => alternarEstado(estado.value)}
                                            className={cn(
                                                'rounded-full border px-3 py-1 text-xs font-medium transition-colors',
                                                activo
                                                    ? ESTADO_TAREA_BADGE_CLASSES[estado.value]
                                                    : 'border-border text-muted-foreground hover:border-verde-3 hover:text-foreground',
                                            )}
                                        >
                                            {estado.label}
                                        </button>
                                    );
                                })}
                            </div>
                        </div>

                        <div className="space-y-2 border-t border-border pt-4">
                            <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Prioridad</p>
                            <div className="flex flex-wrap gap-1.5">
                                {PRIORIDADES_ORDENADAS.map((prioridad) => {
                                    const activo = prioridadesSeleccionadas.includes(prioridad);
                                    return (
                                        <button
                                            key={prioridad}
                                            type="button"
                                            onClick={() => alternarPrioridad(prioridad)}
                                            className={cn(
                                                'rounded-full border px-3 py-1 text-xs font-medium transition-colors',
                                                activo
                                                    ? PRIORIDAD_TAREA_BADGE_CLASSES[prioridad]
                                                    : 'border-border text-muted-foreground hover:border-verde-3 hover:text-foreground',
                                            )}
                                        >
                                            {PRIORIDAD_TAREA_LABELS[prioridad]}
                                        </button>
                                    );
                                })}
                            </div>
                        </div>

                        <div className="space-y-2 border-t border-border pt-4">
                            <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Vencimiento</p>
                            <button
                                type="button"
                                onClick={() => actualizarFiltros(filtros, { solo_atrasadas: !filtros.solo_atrasadas })}
                                className={cn(
                                    'rounded-full border px-3 py-1 text-xs font-medium transition-colors',
                                    filtros.solo_atrasadas
                                        ? ATRASADA_BADGE_CLASSES
                                        : 'border-border text-muted-foreground hover:border-rojo-1/30 hover:text-rojo-1',
                                )}
                            >
                                Solo atrasadas
                            </button>
                        </div>

                        <div className="space-y-2 border-t border-border pt-4">
                            <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Unidad organizacional</p>
                            <Select
                                value={filtros.unidad_organizacional_id ? String(filtros.unidad_organizacional_id) : 'todas'}
                                onValueChange={(valor) =>
                                    actualizarFiltros(filtros, { unidad_organizacional_id: valor === 'todas' ? null : Number(valor) })
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="todas">Todas</SelectItem>
                                    {unidadesOrganizacionales.map((unidad) => (
                                        <SelectItem key={unidad.id} value={String(unidad.id)}>
                                            {unidad.nombre}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </PopoverContent>
                </Popover>
            </div>

            <div className="flex flex-wrap gap-2">
                {FILTROS_RAPIDOS.map((chip) => (
                    <button
                        key={chip.value}
                        type="button"
                        onClick={() => alternarFiltroRapido(chip.value)}
                        className={cn(
                            'rounded-full border-2 px-4 py-1.5 text-sm font-semibold transition-colors',
                            filtros.filtro_rol === chip.value
                                ? 'border-verde-5 bg-verde-2 text-gris-2'
                                : 'border-border text-muted-foreground hover:border-verde-3 hover:text-foreground',
                        )}
                    >
                        {chip.label}
                    </button>
                ))}
            </div>

            <ResumenContadores contadores={contadores} />

            {tareas.length === 0 ? (
                <p className="py-8 text-center text-sm text-muted-foreground">No hay tareas que calcen con estos filtros.</p>
            ) : (
                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    {tareas.map((tarea) => (
                        <TareaCard key={tarea.id} tarea={tarea} onAbrir={onAbrirTarea} />
                    ))}
                </div>
            )}
        </div>
    );
}

export default function MisTareasIndex(props: Props) {
    const [tab, setTab] = useState<Tab>('lista');
    const modal = useTareaDetalleModal();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Mis tareas" />

            <div className="flex flex-col gap-6 p-4">
                <h1 className="text-2xl font-bold text-foreground">Mis tareas</h1>

                <div className="flex gap-1 rounded-md border border-border bg-muted/30 p-1">
                    {TABS.map(({ value, label, icono: Icono }) => (
                        <button
                            key={value}
                            type="button"
                            onClick={() => setTab(value)}
                            className={cn(
                                'flex flex-1 items-center justify-center gap-1.5 rounded-sm px-3 py-1.5 text-sm font-medium transition-colors',
                                tab === value ? 'bg-verde-5 text-gris-2' : 'text-muted-foreground hover:text-foreground',
                            )}
                        >
                            <Icono className="size-4" /> {label}
                        </button>
                    ))}
                </div>

                {tab === 'lista' && <TabLista {...props} onAbrirTarea={modal.abrir} />}

                {tab === 'calendario' && <TabCalendario tareas={props.tareas} usuarios={props.usuarios} onAbrirTarea={modal.abrir} />}

                {tab === 'metricas' && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Panel de métricas</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm text-muted-foreground">Próximamente.</p>
                        </CardContent>
                    </Card>
                )}
            </div>

            <TareaDetalleModal tareaId={modal.tareaId} datos={modal.datos} cargando={modal.cargando} onClose={modal.cerrar} />
        </AppLayout>
    );
}
