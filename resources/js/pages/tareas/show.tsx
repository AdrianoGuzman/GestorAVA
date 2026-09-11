import { AdjuntosSection } from '@/components/tareas/adjuntos-section';
import { AgregarColaboradorDialog } from '@/components/tareas/agregar-colaborador-dialog';
import { ChecklistPersonalSection } from '@/components/tareas/checklist-personal-section';
import { ConfirmarCompletarDialog } from '@/components/tareas/confirmar-completar-dialog';
import { AtrasadaBadge, EstadoBadge } from '@/components/tareas/estado-badge';
import { HistorialTimeline } from '@/components/tareas/historial-timeline';
import { MotivoDialog } from '@/components/tareas/motivo-dialog';
import type { Persona } from '@/components/tareas/persona-picker';
import { ReasignarDialog } from '@/components/tareas/reasignar-dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ROL_USUARIO_LABELS } from '@/lib/estado-tarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import type { ChecklistPersonalItem, PermisosTarea, RolUsuarioTarea, TareaDetalle } from '@/types/tarea';
import { Head } from '@inertiajs/react';
import {
    Ban,
    Calendar,
    CircleCheckBig,
    GitBranch,
    History,
    ListChecks,
    ListTodo,
    MessageSquareWarning,
    Paperclip,
    Plus,
    Undo2,
    User,
    UserCog,
    UserPlus,
    UserX,
    Users,
} from 'lucide-react';

interface Props {
    tarea: TareaDetalle;
    rolUsuario: RolUsuarioTarea;
    usuarios: Persona[];
    checklistPersonal: ChecklistPersonalItem[];
    permisos: PermisosTarea;
}

function formatearFecha(fecha: string | null): string {
    if (!fecha) return '—';
    return new Date(fecha).toLocaleDateString('es-CL');
}

export default function TareaShow({ tarea, rolUsuario, usuarios, checklistPersonal, permisos }: Props) {
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
                                {permisos.puedeReportarProblema && (
                                    <MotivoDialog
                                        tareaId={tarea.id}
                                        routeName="tareas.reportar-problema"
                                        title="Reportar problema"
                                        description="Avisa que la tarea está mal definida. No cambia su estado ni interrumpe el trabajo: solo notifica a quien puede corregirla (el creador si reportás como responsable, el responsable si reportás como colaborador)."
                                        submitLabel="Reportar problema"
                                        submitIcon={MessageSquareWarning}
                                        trigger={
                                            <Button variant="outline">
                                                <MessageSquareWarning /> Reportar problema
                                            </Button>
                                        }
                                    />
                                )}
                                {permisos.puedeReportarNoParticipacion && (
                                    <MotivoDialog
                                        tareaId={tarea.id}
                                        routeName="tareas.no-participar"
                                        title="No puedo ser parte de esto"
                                        description="Avisá que no podés o no querés seguir participando. No te saca de la tarea ni cambia nada por su cuenta: solo notifica a quien puede decidir qué hacer (el creador si sos el responsable, el responsable si sos colaborador)."
                                        submitLabel="Enviar aviso"
                                        submitIcon={UserX}
                                        trigger={
                                            <Button variant="outline">
                                                <UserX /> No puedo ser parte
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

                        <div className="grid grid-cols-2 gap-x-4 gap-y-5 text-sm sm:grid-cols-3">
                            <div>
                                <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Responsable</p>
                                <p className="mt-1 flex items-center gap-1.5 font-medium text-gris-2">
                                    <User className="size-4 shrink-0 text-gris-1" /> {tarea.responsable.name}
                                </p>
                            </div>
                            <div>
                                <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Colaboradores</p>
                                <div className="mt-1 flex items-center gap-1.5">
                                    <Users className="size-4 shrink-0 text-gris-1" />
                                    {tarea.colaboradores.length === 0 ? (
                                        <span className="text-muted-foreground">Nadie más</span>
                                    ) : (
                                        <span className="font-medium text-gris-2">{tarea.colaboradores.map((c) => c.name).join(', ')}</span>
                                    )}
                                    {permisos.puedeAgregarColaborador && (
                                        <AgregarColaboradorDialog
                                            tareaId={tarea.id}
                                            personas={usuarios}
                                            trigger={
                                                <button type="button" className="text-verde-6 hover:text-verde-5" title="Agregar colaborador">
                                                    <Plus className="size-4" />
                                                </button>
                                            }
                                        />
                                    )}
                                </div>
                            </div>
                            <div>
                                <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Creador</p>
                                <p className="mt-1 flex items-center gap-1.5 font-medium text-gris-2">
                                    <User className="size-4 shrink-0 text-gris-1" /> {tarea.creador.name}
                                </p>
                            </div>
                            <div>
                                <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Fecha de inicio</p>
                                <p className="mt-1 flex items-center gap-1.5 font-medium text-gris-2">
                                    <Calendar className="size-4 shrink-0 text-gris-1" /> {formatearFecha(tarea.fecha_inicio)}
                                </p>
                            </div>
                            <div>
                                <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Fecha de compromiso</p>
                                <p className="mt-1 flex items-center gap-1.5 font-medium text-gris-2">
                                    <Calendar className="size-4 shrink-0 text-gris-1" /> {formatearFecha(tarea.fecha_compromiso)}
                                </p>
                            </div>
                            <div>
                                <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                    {tarea.estado === 'cancelada' ? 'Cancelada' : 'Creada'}
                                </p>
                                <p className="mt-1 flex items-center gap-1.5 font-medium text-gris-2">
                                    <Calendar className="size-4 shrink-0 text-gris-1" />{' '}
                                    {tarea.estado === 'cancelada' ? formatearFecha(tarea.fecha_cancelacion) : formatearFecha(tarea.created_at)}
                                </p>
                            </div>
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

                <div className="grid gap-6 lg:grid-cols-3">
                    {permisos.puedeUsarChecklistPersonal && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <ListTodo className="size-4 text-verde-6" /> Mi checklist
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ChecklistPersonalSection tareaId={tarea.id} items={checklistPersonal} />
                            </CardContent>
                        </Card>
                    )}

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
