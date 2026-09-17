import { AplazarEntregaDialog } from '@/components/proyectos/aplazar-entrega-dialog';
import { CrearSeccionDialog } from '@/components/proyectos/crear-seccion-dialog';
import { EditarProyectoDialog } from '@/components/proyectos/editar-proyecto-dialog';
import { EditarSeccionDialog } from '@/components/proyectos/editar-seccion-dialog';
import { HistorialProyectoTimeline } from '@/components/proyectos/historial-proyecto-timeline';
import { CrearTareaDialog } from '@/components/tareas/crear-tarea-dialog';
import { AtrasadaBadge, EstadoBadge, EstadoProyectoBadge } from '@/components/tareas/estado-badge';
import { PersonaAvatar } from '@/components/tareas/persona-avatar';
import type { Persona } from '@/components/tareas/persona-picker';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { ESTADO_TAREA_BADGE_CLASSES } from '@/lib/estado-tarea';
import { cn } from '@/lib/utils';
import type { SharedData } from '@/types';
import type { EstadoTarea } from '@/types/tarea';
import type { ProyectoDetalle, Seccion, TareaDeProyecto } from '@/types/proyecto';
import { usePage } from '@inertiajs/react';
import { Download, FileSpreadsheet, FileText, Plus, Search } from 'lucide-react';
import { useMemo, useState } from 'react';

export interface ProyectoDetalleContentProps {
    proyecto: ProyectoDetalle;
    usuarios: Persona[];
    onAbrirTarea: (id: number) => void;
}

const ESTADOS: { value: EstadoTarea; label: string }[] = [
    { value: 'pendiente', label: 'Pendiente' },
    { value: 'en_progreso', label: 'En progreso' },
    { value: 'completada', label: 'Completada' },
    { value: 'cancelada', label: 'Cancelada' },
];

function formatearFecha(fecha: string | null): string {
    if (!fecha) return '—';
    return new Date(fecha).toLocaleDateString('es-CL');
}

/** Barra de avance simple -- sin dependencia nueva (Radix Progress), solo un div con el ancho en %. */
function BarraAvance({ avance }: { avance: number }) {
    const porcentaje = Math.round(avance * 100);

    return (
        <div className="flex items-center gap-2">
            <div className="h-1.5 flex-1 overflow-hidden rounded-full bg-background">
                <div className="h-full rounded-full bg-verde-5 transition-all" style={{ width: `${porcentaje}%` }} />
            </div>
            <span className="w-9 shrink-0 text-right text-xs font-medium text-muted-foreground">{porcentaje}%</span>
        </div>
    );
}

/** Igual forma que ResumenContadores de "Mis tareas" (mis-tareas/index.tsx), acotado a las tareas de este proyecto. */
function ResumenContadores({ contadores }: { contadores: ProyectoDetalle['contadores'] }) {
    return (
        <div className="flex flex-wrap items-center gap-x-6 gap-y-2 rounded-lg border border-border bg-muted/30 px-5 py-4">
            <span className="text-sm font-bold text-foreground">
                {contadores.total} {contadores.total === 1 ? 'tarea' : 'tareas'} en total
            </span>
            {contadores.atrasadas > 0 && (
                <span className="flex items-center gap-2 text-xs font-medium text-rojo-1">
                    <span className="size-2.5 rounded-full bg-rojo-1" /> {contadores.atrasadas} atrasadas
                </span>
            )}
            {contadores.prioridad_alta > 0 && (
                <span className="flex items-center gap-2 text-xs font-medium text-naranjo-1">
                    <span className="size-2.5 rounded-full bg-naranjo-1" /> {contadores.prioridad_alta} de prioridad alta
                </span>
            )}
            <span className="flex items-center gap-2 text-xs font-medium text-muted-foreground">
                <span className="size-2.5 rounded-full bg-verde-5" /> {contadores.en_progreso} en progreso
            </span>
            <span className="flex items-center gap-2 text-xs font-medium text-muted-foreground">
                <span className="size-2.5 rounded-full bg-gris-1" /> {contadores.pendientes} pendientes
            </span>
            <span className="flex items-center gap-2 text-xs font-medium text-muted-foreground">
                <span className="size-2.5 rounded-full bg-foreground" /> {contadores.completadas} completadas
            </span>
        </div>
    );
}

function ListaTareas({ tareas, onAbrirTarea }: { tareas: TareaDeProyecto[]; onAbrirTarea: (id: number) => void }) {
    if (tareas.length === 0) {
        return <p className="text-xs text-muted-foreground">Sin tareas.</p>;
    }

    return (
        <ul className="space-y-1.5">
            {tareas.map((tarea) => (
                <li key={tarea.id} className="flex items-center gap-2 rounded-md border border-gris-3 bg-background px-2.5 py-1.5 transition-colors hover:bg-muted/50">
                    <PersonaAvatar nombre={tarea.responsable.name} email={tarea.responsable.email} rol="Responsable" className="size-6" />
                    <button
                        type="button"
                        onClick={() => onAbrirTarea(tarea.id)}
                        className="min-w-0 flex-1 truncate text-left text-sm font-medium text-foreground hover:underline"
                    >
                        {tarea.codigo} — {tarea.titulo}
                    </button>
                    {tarea.esta_atrasada && tarea.estado !== 'completada' && <AtrasadaBadge />}
                    <EstadoBadge estado={tarea.estado} />
                </li>
            ))}
        </ul>
    );
}

/**
 * Un objetivo dentro del proyecto: nombre + peso + su propio avance. Fondo
 * plano (sin borde ni sombra, una sola declaración de superficie) para que la
 * separación entre secciones sea notoria sin caer en tarjetas anidadas.
 */
function BloqueSeccion({
    seccion,
    tareasFiltradas,
    puedeAdministrar,
    onAbrirTarea,
}: {
    seccion: Seccion;
    tareasFiltradas: TareaDeProyecto[];
    puedeAdministrar: boolean;
    onAbrirTarea: (id: number) => void;
}) {
    return (
        <div className="space-y-3 rounded-lg bg-muted/50 p-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <div className="flex items-center gap-1">
                    <h3 className="text-sm font-semibold text-foreground">{seccion.nombre}</h3>
                    {puedeAdministrar && <EditarSeccionDialog seccion={seccion} />}
                </div>
                <span className="text-xs text-muted-foreground">peso {Math.round(seccion.peso * 100)}%</span>
            </div>
            <BarraAvance avance={seccion.avance} />
            <ListaTareas tareas={tareasFiltradas} onAbrirTarea={onAbrirTarea} />
        </div>
    );
}

function coincideBusqueda(tarea: TareaDeProyecto, busqueda: string): boolean {
    if (!busqueda) return true;
    const termino = busqueda.trim().toLowerCase();
    return tarea.titulo.toLowerCase().includes(termino) || tarea.codigo.toLowerCase().includes(termino);
}

export function ProyectoDetalleContent({ proyecto, usuarios, onAbrirTarea }: ProyectoDetalleContentProps) {
    const { auth } = usePage<SharedData>().props;
    const puedeAdministrar = auth.puedeAdministrarProyectos;
    const [busqueda, setBusqueda] = useState('');
    const [estadosSeleccionados, setEstadosSeleccionados] = useState<EstadoTarea[]>([]);

    const alternarEstado = (estado: EstadoTarea) => {
        setEstadosSeleccionados((actual) => (actual.includes(estado) ? actual.filter((e) => e !== estado) : [...actual, estado]));
    };

    const coincide = (tarea: TareaDeProyecto) =>
        coincideBusqueda(tarea, busqueda) && (estadosSeleccionados.length === 0 || estadosSeleccionados.includes(tarea.estado));

    const seccionesFiltradas = useMemo(
        () => proyecto.secciones.map((seccion) => ({ seccion, tareas: seccion.tareas.filter(coincide) })),
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [proyecto.secciones, busqueda, estadosSeleccionados],
    );
    const sinSeccionFiltradas = useMemo(
        () => proyecto.tareasSinSeccion.filter(coincide),
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [proyecto.tareasSinSeccion, busqueda, estadosSeleccionados],
    );

    const hayFiltro = busqueda !== '' || estadosSeleccionados.length > 0;
    const sinNada = proyecto.secciones.length === 0 && proyecto.tareasSinSeccion.length === 0;
    const sinResultados = hayFiltro && seccionesFiltradas.every(({ tareas }) => tareas.length === 0) && sinSeccionFiltradas.length === 0;

    return (
        <div className="flex w-full flex-1 flex-col gap-6">
            {/* pr-8: espacio para la "x" de cerrar del modal (DialogContent la pone
                absolute top-4 right-4) -- sin este margen, el boton de editar queda
                pegado justo donde cae esa "x" (mismo fix que tarea-detalle-content.tsx). */}
            <div className="flex flex-wrap items-start justify-between gap-3 border-b border-border pr-8 pb-4">
                <div className="space-y-1.5">
                    <div className="flex flex-wrap items-center gap-2">
                        <h1 className="text-xl font-semibold text-foreground">{proyecto.nombre}</h1>
                        {puedeAdministrar && <EditarProyectoDialog proyecto={proyecto} />}
                        <EstadoProyectoBadge estado={proyecto.estado} />
                    </div>
                    {proyecto.descripcion && <p className="text-sm text-muted-foreground">{proyecto.descripcion}</p>}
                    <p className="text-xs text-muted-foreground">
                        Creado por {proyecto.creador.name} · {formatearFecha(proyecto.fecha_inicio)} — {formatearFecha(proyecto.fecha_termino)}
                    </p>
                </div>

                <div className="flex items-center gap-1">
                    {puedeAdministrar && proyecto.estado === 'activo' && <AplazarEntregaDialog proyecto={proyecto} />}

                    {/* Sin permiso propio: cualquiera que pueda abrir el detalle del
                        proyecto (visible para todos, ver ProyectoController::show())
                        puede exportar el mismo registro que esta viendo. */}
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button size="sm" variant="ghost">
                                <Download /> Exportar
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem asChild>
                                <a href={route('proyectos.exportar-pdf', proyecto.id)}>
                                    <FileText /> Exportar a PDF
                                </a>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <a href={route('proyectos.exportar-excel', proyecto.id)}>
                                    <FileSpreadsheet /> Exportar a Excel
                                </a>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <div className="space-y-1">
                <p className="text-xs font-medium text-muted-foreground">Avance total</p>
                <BarraAvance avance={proyecto.avance} />
            </div>

            {!sinNada && (
                <>
                    <ResumenContadores contadores={proyecto.contadores} />

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
                </>
            )}

            {sinNada && <p className="text-sm text-muted-foreground">Sin tareas asociadas todavía.</p>}
            {sinResultados && <p className="text-sm text-muted-foreground">Ninguna tarea coincide con el filtro.</p>}

            {seccionesFiltradas.map(({ seccion, tareas }) => (
                <BloqueSeccion key={seccion.id} seccion={seccion} tareasFiltradas={tareas} puedeAdministrar={puedeAdministrar} onAbrirTarea={onAbrirTarea} />
            ))}

            {(sinSeccionFiltradas.length > 0 || (!hayFiltro && proyecto.tareasSinSeccion.length > 0)) && (
                <div className="space-y-3 rounded-lg bg-muted/50 p-4">
                    <h3 className="text-sm font-semibold text-muted-foreground">Sin sección</h3>
                    <ListaTareas tareas={sinSeccionFiltradas} onAbrirTarea={onAbrirTarea} />
                </div>
            )}

            {puedeAdministrar && proyecto.estado === 'activo' && (
                <div className="flex flex-wrap gap-2 border-t border-border pt-4">
                    <CrearSeccionDialog proyectoId={proyecto.id} />
                    <CrearTareaDialog
                        personas={usuarios}
                        proyectos={[{ id: proyecto.id, nombre: proyecto.nombre, fecha_inicio: proyecto.fecha_inicio, fecha_termino: proyecto.fecha_termino }]}
                        secciones={proyecto.secciones.map((seccion) => ({ id: seccion.id, nombre: seccion.nombre, proyecto_id: proyecto.id }))}
                        proyectoIdInicial={proyecto.id}
                        trigger={
                            <Button type="button" variant="outline" size="sm" className="w-fit">
                                <Plus /> Crear tarea
                            </Button>
                        }
                    />
                </div>
            )}

            <div className="border-t border-border pt-4">
                <HistorialProyectoTimeline eventos={proyecto.historial} />
            </div>
        </div>
    );
}
