import { AdjuntosSection } from '@/components/tareas/adjuntos-section';
import { AgregarColaboradorDialog } from '@/components/tareas/agregar-colaborador-dialog';
import { ConfirmarCompletarDialog } from '@/components/tareas/confirmar-completar-dialog';
import { AtrasadaBadge, EstadoBadge } from '@/components/tareas/estado-badge';
import { HistorialTimeline } from '@/components/tareas/historial-timeline';
import { MotivoDialog } from '@/components/tareas/motivo-dialog';
import { PersonaAvatar } from '@/components/tareas/persona-avatar';
import type { Persona } from '@/components/tareas/persona-picker';
import { ReasignarDialog } from '@/components/tareas/reasignar-dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ROL_USUARIO_LABELS } from '@/lib/estado-tarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import type { PermisosTarea, RolUsuarioTarea, TareaDetalle } from '@/types/tarea';
import { Head } from '@inertiajs/react';
import { Ban, CircleCheckBig, GitBranch, History, ListChecks, Paperclip, Plus, Undo2, UserCog, UserPlus, XCircle } from 'lucide-react';

interface Props {
    tarea: TareaDetalle;
    rolUsuario: RolUsuarioTarea;
    usuarios: Persona[];
    permisos: PermisosTarea;
}

function formatearFecha(fecha: string | null): string {
    if (!fecha) return '—';
    return new Date(fecha).toLocaleDateString('es-CL');
}

export default function TareaShow({ tarea, rolUsuario, usuarios, permisos }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: tarea.titulo, href: `/tareas/${tarea.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={tarea.titulo} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <Card className="overflow-hidden border-t-4 border-t-verde-5">
                    <CardHeader>
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div className="space-y-2">
                                <div className="flex flex-wrap items-center gap-2">
                                    <CardTitle>{tarea.titulo}</CardTitle>
                                    {rolUsuario && (
                                        <span className="inline-flex items-center rounded-full border border-verde-3 bg-verde-2 px-2.5 py-0.5 text-xs font-medium text-gris-2">
                                            Tu rol: {ROL_USUARIO_LABELS[rolUsuario]}
                                        </span>
                                    )}
                                </div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <EstadoBadge estado={tarea.estado} />
                                    {tarea.esta_atrasada && <AtrasadaBadge />}
                                </div>

                                <div className="flex items-center gap-2">
                                    <div className="flex items-center -space-x-2">
                                        <PersonaAvatar nombre={tarea.responsable.name} destacado />
                                        {tarea.colaboradores.map((colaborador) => (
                                            <PersonaAvatar key={colaborador.id} nombre={colaborador.name} />
                                        ))}
                                        {permisos.puedeAgregarColaborador && (
                                            <AgregarColaboradorDialog
                                                tareaId={tarea.id}
                                                personas={usuarios}
                                                trigger={
                                                    <button
                                                        type="button"
                                                        className="flex size-8 items-center justify-center rounded-full border-2 border-dashed border-gris-3 bg-background text-gris-1 transition-colors hover:border-verde-5 hover:text-verde-6"
                                                        title="Agregar colaborador"
                                                    >
                                                        <Plus className="size-4" />
                                                    </button>
                                                }
                                            />
                                        )}
                                    </div>
                                    <p className="text-xs text-muted-foreground">Creado por {tarea.creador.name}</p>
                                </div>
                            </div>

                            <div className="flex flex-wrap gap-2">
                                {permisos.puedeCompletar && (
                                    <ConfirmarCompletarDialog
                                        tareaId={tarea.id}
                                        trigger={
                                            <Button>
                                                <CircleCheckBig /> Completar
                                            </Button>
                                        }
                                    />
                                )}
                                {permisos.puedeReasignar && (
                                    <ReasignarDialog
                                        tareaId={tarea.id}
                                        personas={usuarios}
                                        trigger={
                                            <Button variant="outline">
                                                <UserCog /> Reasignar
                                            </Button>
                                        }
                                    />
                                )}
                                {permisos.puedeAgregarColaborador && (
                                    <AgregarColaboradorDialog
                                        tareaId={tarea.id}
                                        personas={usuarios}
                                        trigger={
                                            <Button variant="outline">
                                                <UserPlus /> Agregar colaborador
                                            </Button>
                                        }
                                    />
                                )}
                                {permisos.puedeRetroceder && (
                                    <MotivoDialog
                                        tareaId={tarea.id}
                                        routeName="tareas.retroceder"
                                        title="Retroceder a Pendiente"
                                        description="Devuelve la tarea a Pendiente para reordenar tu propio trabajo. El responsable y los colaboradores no cambian."
                                        submitLabel="Retroceder"
                                        submitIcon={Undo2}
                                        trigger={
                                            <Button variant="outline">
                                                <Undo2 /> Retroceder
                                            </Button>
                                        }
                                    />
                                )}
                                {permisos.puedeRechazar && (
                                    <MotivoDialog
                                        tareaId={tarea.id}
                                        routeName="tareas.rechazar"
                                        title="Rechazar tarea"
                                        description="La tarea vuelve a quien la delegó por última vez para que la corrija y reasigne."
                                        submitLabel="Rechazar"
                                        submitIcon={XCircle}
                                        variant="destructive"
                                        trigger={
                                            <Button variant="outline">
                                                <XCircle /> Rechazar
                                            </Button>
                                        }
                                    />
                                )}
                                {permisos.puedeCancelar && (
                                    <MotivoDialog
                                        tareaId={tarea.id}
                                        routeName="tareas.cancelar"
                                        title="Cancelar tarea"
                                        description="Cierre definitivo: la tarea no vuelve a nadie y no se puede revertir."
                                        submitLabel="Cancelar tarea"
                                        submitIcon={Ban}
                                        variant="destructive"
                                        trigger={
                                            <Button variant="destructive">
                                                <Ban /> Cancelar
                                            </Button>
                                        }
                                    />
                                )}
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <div>
                            <h3 className="mb-2 text-sm font-semibold text-gris-2">Descripción</h3>
                            <p className="rounded-md border border-border bg-muted/40 p-3 text-sm text-gris-2">
                                {tarea.descripcion || <span className="text-muted-foreground">Sin descripción.</span>}
                            </p>
                        </div>

                        <div className="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                            <div>
                                <p className="text-muted-foreground">Creada</p>
                                <p className="font-medium text-gris-2">{formatearFecha(tarea.created_at)}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground">Fecha de inicio</p>
                                <p className="font-medium text-gris-2">{formatearFecha(tarea.fecha_inicio)}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground">Fecha de compromiso</p>
                                <p className="font-medium text-gris-2">{formatearFecha(tarea.fecha_compromiso)}</p>
                            </div>
                            {tarea.estado === 'cancelada' && (
                                <div>
                                    <p className="text-muted-foreground">Cancelada</p>
                                    <p className="font-medium text-gris-2">{formatearFecha(tarea.fecha_cancelacion)}</p>
                                </div>
                            )}
                        </div>

                        {tarea.estado === 'cancelada' && tarea.motivo_cancelacion && (
                            <p className="rounded-md border border-gris-1/30 bg-gris-1/10 p-3 text-sm text-gris-2">
                                Motivo de cancelación: {tarea.motivo_cancelacion}
                            </p>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Paperclip className="size-4 text-verde-6" /> Adjuntos
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <AdjuntosSection tareaId={tarea.id} adjuntos={tarea.adjuntos} puedeAdjuntar={permisos.puedeAdjuntar} />
                    </CardContent>
                </Card>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <ListChecks className="size-4 text-verde-6" /> Checklist
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm text-muted-foreground">Próximamente (RF-23) — módulo a cargo de Jeremy.</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <GitBranch className="size-4 text-verde-6" /> Dependencias
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm text-muted-foreground">Próximamente (RF-21/RF-22) — módulo a cargo de Oscar.</p>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <History className="size-4 text-verde-6" /> Historial
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <HistorialTimeline eventos={tarea.historial} />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
