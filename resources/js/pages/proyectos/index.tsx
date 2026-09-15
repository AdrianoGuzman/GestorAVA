import { CrearProyectoDialog } from '@/components/proyectos/crear-proyecto-dialog';
import { EditarProyectoDialog } from '@/components/proyectos/editar-proyecto-dialog';
import { ProyectoDetalleModal, useProyectoDetalleModal } from '@/components/proyectos/proyecto-detalle-modal';
import { EstadoProyectoBadge } from '@/components/tareas/estado-badge';
import { TareaDetalleModal, useTareaDetalleModal } from '@/components/tareas/tarea-detalle-modal';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem, SharedData } from '@/types';
import type { ProyectoResumen } from '@/types/proyecto';
import { Head, usePage } from '@inertiajs/react';
import { FolderKanban } from 'lucide-react';
import { useState } from 'react';

interface Props {
    proyectos: ProyectoResumen[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Mis proyectos', href: '/mis-proyectos' }];

function formatearFecha(fecha: string | null): string {
    if (!fecha) return '—';
    return new Date(fecha).toLocaleDateString('es-CL');
}

/** Barra de avance simple -- sin dependencia nueva (Radix Progress), solo un div con el ancho en %. */
function BarraAvance({ avance }: { avance: number }) {
    const porcentaje = Math.round(avance * 100);

    return (
        <div className="flex items-center gap-2">
            <div className="h-1.5 flex-1 overflow-hidden rounded-full bg-muted">
                <div className="h-full rounded-full bg-verde-5 transition-all" style={{ width: `${porcentaje}%` }} />
            </div>
            <span className="w-9 shrink-0 text-right text-xs font-medium text-muted-foreground">{porcentaje}%</span>
        </div>
    );
}

/**
 * Tarjeta compacta: solo info básica -- el detalle completo (secciones,
 * tareas) se abre en un modal (ver proyecto-detalle-modal.tsx), no navega a
 * otra página. Toda la tarjeta es clickeable (botón a inset-0 detrás del
 * contenido) salvo el botón de editar, que va encima en su propia capa para
 * no disparar la apertura del modal al usarlo.
 */
function ProyectoCard({
    proyecto,
    puedeAdministrar,
    onAbrir,
}: {
    proyecto: ProyectoResumen;
    puedeAdministrar: boolean;
    onAbrir: (id: number) => void;
}) {
    return (
        <Card className={cn('relative transition-colors hover:border-verde-3', proyecto.estado === 'cerrado' && 'opacity-70')}>
            <button
                type="button"
                onClick={() => onAbrir(proyecto.id)}
                className="absolute inset-0"
                aria-label={proyecto.nombre}
            />
            <CardHeader className="flex flex-row items-start justify-between gap-3">
                <div className="pointer-events-none min-w-0 space-y-1.5">
                    <div className="flex flex-wrap items-center gap-2">
                        <CardTitle className="text-base">{proyecto.nombre}</CardTitle>
                        <EstadoProyectoBadge estado={proyecto.estado} />
                    </div>
                    {proyecto.descripcion && <p className="text-sm text-muted-foreground">{proyecto.descripcion}</p>}
                    <p className="text-xs text-muted-foreground">
                        Creado por {proyecto.creador.name} · {formatearFecha(proyecto.fecha_inicio)} — {formatearFecha(proyecto.fecha_termino)}
                    </p>
                </div>
                {puedeAdministrar && (
                    <div className="relative z-10">
                        <EditarProyectoDialog proyecto={proyecto} />
                    </div>
                )}
            </CardHeader>
            <CardContent className="pointer-events-none">
                <BarraAvance avance={proyecto.avance} />
            </CardContent>
        </Card>
    );
}

export default function ProyectosIndex({ proyectos }: Props) {
    const { auth } = usePage<SharedData>().props;
    const modalProyecto = useProyectoDetalleModal();
    const modalTarea = useTareaDetalleModal();
    // Franco (15-09-2026): tener los dos <Dialog> de Radix abiertos (open=true)
    // al mismo tiempo -- aunque sean no-modal -- resulto ambiguo (el boton
    // "Volver" no aparecia, la "x" cerraba de mas). En vez de eso, abrir una
    // tarea desde un proyecto CIERRA de verdad el modal del proyecto,
    // recordando a cual volver -- nunca hay dos Dialog abiertos a la vez.
    const [proyectoPausado, setProyectoPausado] = useState<{ id: number; nombre: string } | null>(null);

    const abrirTareaDesdeProyecto = (id: number) => {
        if (modalProyecto.proyectoId !== null) {
            setProyectoPausado({ id: modalProyecto.proyectoId, nombre: modalProyecto.datos?.proyecto.nombre ?? 'proyecto' });
        }
        modalProyecto.cerrar();
        modalTarea.abrir(id);
    };

    const volverAlProyectoPausado = () => {
        modalTarea.cerrar();
        if (proyectoPausado !== null) {
            modalProyecto.abrir(proyectoPausado.id);
            setProyectoPausado(null);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Mis proyectos" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-bold text-foreground">Mis proyectos</h1>
                    {auth.puedeAdministrarProyectos && <CrearProyectoDialog />}
                </div>

                {proyectos.length === 0 ? (
                    <div className="flex flex-col items-center gap-2 rounded-lg border border-dashed border-border py-16 text-center text-muted-foreground">
                        <FolderKanban className="size-8" />
                        <p className="text-sm">Todavía no hay proyectos.</p>
                        {!auth.puedeAdministrarProyectos && (
                            <p className="text-xs">Solo Directorio y Gerencia pueden crear uno.</p>
                        )}
                    </div>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2">
                        {proyectos.map((proyecto) => (
                            <ProyectoCard
                                key={proyecto.id}
                                proyecto={proyecto}
                                puedeAdministrar={auth.puedeAdministrarProyectos}
                                onAbrir={modalProyecto.abrir}
                            />
                        ))}
                    </div>
                )}
            </div>

            <ProyectoDetalleModal
                proyectoId={modalProyecto.proyectoId}
                datos={modalProyecto.datos}
                cargando={modalProyecto.cargando}
                onClose={modalProyecto.cerrar}
                onAbrirTarea={abrirTareaDesdeProyecto}
            />

            <TareaDetalleModal
                tareaId={modalTarea.tareaId}
                datos={modalTarea.datos}
                cargando={modalTarea.cargando}
                onClose={proyectoPausado !== null ? volverAlProyectoPausado : modalTarea.cerrar}
                refrescar={modalTarea.refrescar}
                abrirRelacionada={modalTarea.abrirRelacionada}
                volver={modalTarea.volver}
                puedeVolver={modalTarea.puedeVolver}
                volverAlOrigen={proyectoPausado !== null ? { etiqueta: `Volver a ${proyectoPausado.nombre}`, onClick: volverAlProyectoPausado } : null}
            />
        </AppLayout>
    );
}
