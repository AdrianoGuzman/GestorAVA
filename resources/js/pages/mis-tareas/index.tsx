import { CrearTareaDialog } from '@/components/tareas/crear-tarea-dialog';
import { AtrasadaBadge, EstadoBadge } from '@/components/tareas/estado-badge';
import type { Persona } from '@/components/tareas/persona-picker';
import { TareaDetalleModal, useTareaDetalleModal } from '@/components/tareas/tarea-detalle-modal';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ROL_USUARIO_LABELS } from '@/lib/estado-tarea';
import { cn } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import type { EstadoTarea, FiltroRolMisTareas, FiltrosMisTareas, TareaResumen } from '@/types/tarea';
import { Head, router } from '@inertiajs/react';
import { Calendar, Gauge, ListFilter, ListTodo, Plus, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface Props {
    tareas: TareaResumen[];
    contadores: { total: number; atrasadas: number; en_progreso: number; pendientes: number; completadas: number };
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

function actualizarFiltros(filtros: FiltrosMisTareas, cambios: Partial<FiltrosMisTareas>) {
    router.get(
        route('mis-tareas.index'),
        { ...filtros, ...cambios },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function TareaCard({ tarea, onAbrir }: { tarea: TareaResumen; onAbrir: (id: number) => void }) {
    const mostrarVencimiento = tarea.esta_atrasada || esManana(tarea.fecha_compromiso);

    return (
        <button
            type="button"
            onClick={() => onAbrir(tarea.id)}
            className="block w-full rounded-lg border border-border p-4 text-left transition-colors hover:border-verde-5 hover:bg-muted/30"
        >
            <div className="flex flex-wrap items-start justify-between gap-2">
                <div className="min-w-0">
                    <p className="text-xs text-muted-foreground">{tarea.codigo}</p>
                    <p className="truncate font-semibold text-foreground">{tarea.titulo}</p>
                </div>
                {mostrarVencimiento && <AtrasadaBadge className={tarea.esta_atrasada ? undefined : 'border-gris-1/30 bg-gris-1/10 text-gris-1'} />}
            </div>

            <div className="mt-2 space-y-0.5 text-sm text-muted-foreground">
                {tarea.unidad_organizacional && <p>Obra: {tarea.unidad_organizacional.nombre}</p>}
                <p>
                    {tarea.responsable.name} · {ROL_USUARIO_LABELS[tarea.rol]}
                </p>
                <p>Vence: {formatearFecha(tarea.fecha_compromiso)}</p>
            </div>

            <div className="mt-3 flex items-center justify-between">
                <EstadoBadge estado={tarea.estado} />
                <span className="text-sm font-medium text-verde-6">Ver tarea →</span>
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
    const filtrosActivos = estadosSeleccionados.length > 0 || !!filtros.solo_atrasadas || !!filtros.unidad_organizacional_id;

    const alternarEstado = (estado: EstadoTarea) => {
        const siguiente = estadosSeleccionados.includes(estado)
            ? estadosSeleccionados.filter((e) => e !== estado)
            : [...estadosSeleccionados, estado];
        actualizarFiltros(filtros, { estado: siguiente });
    };

    const alternarFiltroRapido = (rol: FiltroRolMisTareas) => {
        actualizarFiltros(filtros, { filtro_rol: filtros.filtro_rol === rol ? undefined : rol });
    };

    const alternarSoloAtrasadas = () => {
        actualizarFiltros(filtros, { solo_atrasadas: !filtros.solo_atrasadas });
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
                    <PopoverContent className="w-72 space-y-4" align="end">
                        <div className="space-y-2">
                            <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Estado</p>
                            {ESTADOS.map((estado) => (
                                <label key={estado.value} className="flex items-center gap-2 text-sm">
                                    <Checkbox
                                        checked={estadosSeleccionados.includes(estado.value)}
                                        onCheckedChange={() => alternarEstado(estado.value)}
                                    />
                                    {estado.label}
                                </label>
                            ))}
                        </div>

                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox checked={!!filtros.solo_atrasadas} onCheckedChange={alternarSoloAtrasadas} />
                            Solo atrasadas
                        </label>

                        <div className="space-y-2">
                            <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Obra</p>
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
                                    {unidadesOrganizacionales.map((obra) => (
                                        <SelectItem key={obra.id} value={String(obra.id)}>
                                            {obra.nombre}
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

                {tab !== 'lista' && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">{TABS.find((t) => t.value === tab)?.label}</CardTitle>
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
