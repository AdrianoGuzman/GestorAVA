import { AdjuntosSection } from '@/components/tareas/adjuntos-section';
import { AgregarColaboradorDialog } from '@/components/tareas/agregar-colaborador-dialog';
import { ChecklistPersonalSection } from '@/components/tareas/checklist-personal-section';
import { ChecklistSection } from '@/components/tareas/checklist-section';
import { ConfirmarCompletarDialog } from '@/components/tareas/confirmar-completar-dialog';
import { DependenciasSection } from '@/components/tareas/dependencias-section';
import { EditarTareaDialog } from '@/components/tareas/editar-tarea-dialog';
import { AtrasadaBadge, EstadoBadge, NivelJerarquicoBadge, PrioridadBadge } from '@/components/tareas/estado-badge';
import { HistorialInline } from '@/components/tareas/historial-timeline';
import { MotivoDialog } from '@/components/tareas/motivo-dialog';
import { PersonaAvatar } from '@/components/tareas/persona-avatar';
import type { Persona } from '@/components/tareas/persona-picker';
import { ReasignarDialog } from '@/components/tareas/reasignar-dialog';
import { TareaModalContext } from '@/components/tareas/tarea-modal-context';
import { Button } from '@/components/ui/button';
import { stringAFecha } from '@/components/ui/date-picker-button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { calcularHorasAtrasoEntrega, ENTREGADA_CON_ATRASO_BADGE_CLASSES, formatearDuracionAtraso, ROL_USUARIO_LABELS } from '@/lib/estado-tarea';
import { cn } from '@/lib/utils';
import type { AdjuntoDeTareaHija, ChecklistPersonalItem, PermisosTarea, ProyectoResumen, RolUsuarioTarea, SeccionResumen, TareaDetalle } from '@/types/tarea';
import {
    ArrowLeft,
    Ban,
    Calendar,
    CircleCheckBig,
    Download,
    FileSpreadsheet,
    FileText,
    FolderKanban,
    GitBranch,
    History,
    ListTodo,
    ListTree,
    MessageSquareWarning,
    MoreHorizontal,
    Paperclip,
    Pencil,
    Plus,
    User,
    UserX,
    Users,
} from 'lucide-react';
import { ReactNode, useContext, useRef, useState } from 'react';

export interface TareaDetalleContentProps {
    tarea: TareaDetalle;
    rolUsuario: RolUsuarioTarea;
    usuarios: Persona[];
    proyectos: ProyectoResumen[];
    secciones: SeccionResumen[];
    checklistPersonal: ChecklistPersonalItem[];
    adjuntosDeTareasHijas: AdjuntoDeTareaHija[];
    permisos: PermisosTarea;
}

function formatearFecha(fecha: string | null): string {
    if (!fecha) return '—';
    return stringAFecha(fecha.slice(0, 10)).toLocaleDateString('es-CL');
}

/** Plazo = diferencia entre fecha_inicio y fecha_compromiso (fecha término). */
function calcularPlazo(fechaInicio: string | null, fechaCompromiso: string): string {
    if (!fechaInicio) return '—';
    const dias = Math.round((new Date(fechaCompromiso).getTime() - new Date(fechaInicio).getTime()) / 86_400_000);
    return `${dias} día${dias === 1 ? '' : 's'}`;
}

/**
 * Fila de metadata estilo Asana: etiqueta angosta a la izquierda, valor a
 * la derecha -- reemplaza los bloques "etiqueta arriba, valor abajo" que
 * antes vivian repartidos entre el header y una barra lateral propia.
 */
function FilaMetadata({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="flex items-center gap-4 py-2">
            <span className="w-36 shrink-0 text-sm text-muted-foreground">{label}</span>
            <div className="flex flex-1 flex-wrap items-center gap-2 text-sm text-foreground">{children}</div>
        </div>
    );
}

/**
 * RF-24: contenido de la vista de detalle de una tarea, sin el AppLayout ni
 * el Head -- extraido de la pagina para poder reusarlo tal cual dentro del
 * modal de "Mis tareas" (ver tarea-detalle-modal.tsx), que muestra esto
 * mismo sin navegar fuera del listado.
 *
 * Estructura tomada de una referencia de Asana que el equipo penso que
 * resolvia mejor el desorden de la version anterior (cajas anidadas,
 * historial escondido en un panel aparte): una sola columna sin tarjetas,
 * filas de metadata compactas, acciones en una barra separada arriba del
 * contenido, y el historial integrado al final como "Actividad" en vez de
 * oculto en un Sheet.
 */
export function TareaDetalleContent({ tarea, rolUsuario, usuarios, proyectos, secciones, checklistPersonal, adjuntosDeTareasHijas, permisos }: TareaDetalleContentProps) {
    const [problemaAbierto, setProblemaAbierto] = useState(false);
    const [noParticiparAbierto, setNoParticiparAbierto] = useState(false);
    const actividadRef = useRef<HTMLDivElement>(null);
    const modal = useContext(TareaModalContext);

    const hayMasAcciones = permisos.puedeReportarProblema || permisos.puedeReportarNoParticipacion;

    // Quien ya participa (responsable o colaborador) no debe volver a
    // aparecer como opcion al agregar un nuevo colaborador.
    const colaboradoresPotenciales = usuarios.filter(
        (usuario) => usuario.id !== tarea.responsable.id && !tarea.colaboradores.some((colaborador) => colaborador.id === usuario.id),
    );

    // El responsable actual no es un candidato valido para "reasignar" --
    // reasignarle la tarea a si mismo no tiene sentido y solo confunde el
    // selector.
    const candidatosAResponsable = usuarios.filter((usuario) => usuario.id !== tarea.responsable.id);

    return (
        <div className="flex w-full flex-1 flex-col">
            {/* Solo aparece si se llego aca desde otra tarea (hija/dependencia) sin
                cerrar el modal -- ver TareaModalContext.abrirRelacionada. */}
            {modal?.puedeVolver && (
                <button
                    type="button"
                    onClick={modal.volver}
                    className="mb-2 flex w-fit items-center gap-1 text-xs text-muted-foreground transition-colors hover:text-foreground hover:underline"
                >
                    <ArrowLeft className="size-3.5" /> Volver a la tarea anterior
                </button>
            )}

            {/* Solo aparece si este modal se abrio encima de otro (ej. una tarea
                abierta desde el detalle de un Proyecto) -- ver TareaModalContext.volverAlOrigen. */}
            {modal?.volverAlOrigen && (
                <button
                    type="button"
                    onClick={modal.volverAlOrigen.onClick}
                    className="mb-2 flex w-fit items-center gap-1 text-xs text-muted-foreground transition-colors hover:text-foreground hover:underline"
                >
                    <ArrowLeft className="size-3.5" /> {modal.volverAlOrigen.etiqueta}
                </button>
            )}

            {/* Barra de acciones: separada del contenido, como el titulo+acciones
                de Asana en vez de repartidas entre el header y una columna lateral.
                pr-8: espacio para la "x" de cerrar del modal (DialogContent la
                pone absolute top-4 right-4) -- sin este margen, el ultimo boton
                de la fila (antes "Más acciones", ahora tambien "Exportar") queda
                pegado justo donde cae esa "x". */}
            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-border pr-8 pb-4">
                <div className="flex items-center gap-2">
                    <h1 className="text-xl font-semibold text-foreground">{tarea.titulo}</h1>
                    {permisos.puedeEditar && (
                        <EditarTareaDialog
                            tareaId={tarea.id}
                            titulo={tarea.titulo}
                            descripcion={tarea.descripcion}
                            fechaInicio={tarea.fecha_inicio}
                            fechaCompromiso={tarea.fecha_compromiso}
                            prioridad={tarea.prioridad}
                            evidenciaObligatoria={tarea.evidencia_obligatoria}
                            proyectoId={tarea.proyecto?.id ?? null}
                            seccionId={tarea.seccion?.id ?? null}
                            proyectos={proyectos}
                            secciones={secciones}
                            trigger={
                                <button type="button" className="rounded-full p-1 -m-1 text-verde-6 transition-colors hover:bg-verde-1 hover:text-verde-6 active:bg-verde-2" title="Editar tarea">
                                    <Pencil className="size-4" />
                                </button>
                            }
                        />
                    )}
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    {rolUsuario && (
                        <span className="inline-flex items-center rounded-full border border-verde-3 bg-verde-2 px-2.5 py-0.5 text-xs font-medium text-gris-2">
                            Tu rol: {ROL_USUARIO_LABELS[rolUsuario]}
                        </span>
                    )}
                    {tarea.historial.length > 0 && (
                        <Button
                            size="sm"
                            variant="ghost"
                            onClick={() => actividadRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' })}
                        >
                            <History /> Actividad
                            <span className="rounded-full bg-verde-2 px-1.5 py-0.5 text-xs font-semibold text-gris-2">
                                {tarea.historial.length}
                            </span>
                        </Button>
                    )}
                    {/* Sin permiso propio: cualquiera que pueda abrir el detalle de la
                        tarea (ver TareaController::show(), sin restriccion adicional)
                        puede exportar el mismo registro que esta viendo. */}
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button size="sm" variant="ghost">
                                <Download /> Exportar
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem asChild>
                                <a href={route('tareas.exportar-pdf', tarea.id)}>
                                    <FileText /> Exportar a PDF
                                </a>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <a href={route('tareas.exportar-excel', tarea.id)}>
                                    <FileSpreadsheet /> Exportar a Excel
                                </a>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                    {permisos.puedeCompletar && (
                        <ConfirmarCompletarDialog
                            tareaId={tarea.id}
                            trigger={
                                <Button size="sm">
                                    <CircleCheckBig /> Completar
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
                                <Button size="sm" variant="destructive">
                                    <Ban /> Cancelar
                                </Button>
                            }
                        />
                    )}
                    {hayMasAcciones && (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button size="sm" variant="ghost">
                                    <MoreHorizontal /> Más acciones
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                {permisos.puedeReportarProblema && (
                                    <DropdownMenuItem onSelect={() => setProblemaAbierto(true)}>
                                        <MessageSquareWarning /> Notificar problema
                                    </DropdownMenuItem>
                                )}
                                {permisos.puedeReportarNoParticipacion && (
                                    <DropdownMenuItem onSelect={() => setNoParticiparAbierto(true)}>
                                        <UserX /> No puedo ser parte
                                    </DropdownMenuItem>
                                )}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}
                </div>
            </div>

            {/* Dialogos de "Más acciones": sin trigger propio, se abren solo via
                el estado de arriba (ver nota en MotivoDialog sobre por que no
                van directo dentro de un DropdownMenuItem). */}
            {permisos.puedeReportarProblema && (
                <MotivoDialog
                    tareaId={tarea.id}
                    routeName="tareas.reportar-problema"
                    title="Reportar problema"
                    description="Avisa que la tarea está mal definida. No cambia su estado ni interrumpe el trabajo: solo notifica a quien puede corregirla (el creador si reportás como responsable, el responsable si reportás como colaborador)."
                    submitLabel="Reportar problema"
                    submitIcon={MessageSquareWarning}
                    open={problemaAbierto}
                    onOpenChange={setProblemaAbierto}
                />
            )}
            {permisos.puedeReportarNoParticipacion && (
                <MotivoDialog
                    tareaId={tarea.id}
                    routeName="tareas.no-participar"
                    title="No puedo ser parte de esto"
                    description="Avisa que no puedes o no quieres seguir participando. No te saca de la tarea ni cambia nada por su cuenta: solo notifica a quien puede decidir qué hacer (el creador si eres el responsable, el responsable si eres colaborador)."
                    submitLabel="Enviar aviso"
                    submitIcon={UserX}
                    open={noParticiparAbierto}
                    onOpenChange={setNoParticiparAbierto}
                />
            )}

            {/* Metadata: filas simples etiqueta/valor, una sola columna. */}
            <div className="divide-y divide-border/60 border-b border-border">
                <FilaMetadata label="Estado">
                    <EstadoBadge estado={tarea.estado} />
                    <PrioridadBadge prioridad={tarea.prioridad} />
                    {tarea.esta_atrasada && tarea.estado === 'completada' && (
                        <AtrasadaBadge
                            label={`Entregada con ${formatearDuracionAtraso(calcularHorasAtrasoEntrega(tarea.fecha_compromiso, tarea.updated_at))} de atraso`}
                            className={ENTREGADA_CON_ATRASO_BADGE_CLASSES}
                        />
                    )}
                    {tarea.esta_atrasada && tarea.estado !== 'completada' && <AtrasadaBadge />}
                </FilaMetadata>

                <FilaMetadata label="Responsable">
                    <User className="size-4 shrink-0 text-gris-1" />
                    <span className="font-medium">{tarea.responsable.name}</span>
                    {tarea.responsable.nivel_jerarquico && <NivelJerarquicoBadge nivel={tarea.responsable.nivel_jerarquico} />}
                    {permisos.puedeReasignar && (
                        <ReasignarDialog
                            tareaId={tarea.id}
                            personas={candidatosAResponsable}
                            trigger={
                                <button type="button" className="rounded-full p-1 -m-1 text-verde-6 transition-colors hover:bg-verde-1 hover:text-verde-6 active:bg-verde-2" title="Reasignar responsable">
                                    <Pencil className="size-4" />
                                </button>
                            }
                        />
                    )}
                </FilaMetadata>

                <FilaMetadata label="Colaboradores">
                    {tarea.colaboradores.length === 0 ? (
                        <>
                            <Users className="size-4 shrink-0 text-gris-1" />
                            <span className="text-muted-foreground">Nadie más</span>
                        </>
                    ) : (
                        tarea.colaboradores.map((colaborador) => (
                            <PersonaAvatar key={colaborador.id} nombre={colaborador.name} email={colaborador.email} rol="Colaborador" />
                        ))
                    )}
                    {permisos.puedeAgregarColaborador && (
                        <AgregarColaboradorDialog
                            tareaId={tarea.id}
                            personas={colaboradoresPotenciales}
                            trigger={
                                <button type="button" className="rounded-full p-1 -m-1 text-verde-6 transition-colors hover:bg-verde-1 hover:text-verde-6 active:bg-verde-2" title="Agregar colaborador">
                                    <Plus className="size-4" />
                                </button>
                            }
                        />
                    )}
                </FilaMetadata>

                <FilaMetadata label="Proyecto">
                    <FolderKanban className="size-4 shrink-0 text-gris-1" />
                    <span className={tarea.proyecto ? 'font-medium' : 'text-muted-foreground'}>
                        {tarea.proyecto?.nombre ?? 'Sin proyecto'}
                    </span>
                    {tarea.proyecto && (
                        <span className="text-muted-foreground">— {tarea.seccion?.nombre ?? 'Sin sección'}</span>
                    )}
                </FilaMetadata>

                <FilaMetadata label="Fecha inicio / término">
                    <Calendar className="size-4 shrink-0 text-gris-1" />
                    <span>
                        {tarea.fecha_inicio ? formatearFecha(tarea.fecha_inicio) : 'Sin definir'} — {formatearFecha(tarea.fecha_compromiso)}
                    </span>
                </FilaMetadata>

                <FilaMetadata label="Plazo">{calcularPlazo(tarea.fecha_inicio, tarea.fecha_compromiso)}</FilaMetadata>
            </div>

            {/* Descripción */}
            <div className="border-b border-border py-4">
                <h3 className="mb-2 text-sm font-semibold text-foreground">Descripción</h3>
                <p className="text-sm whitespace-pre-wrap text-foreground">
                    {tarea.descripcion || <span className="text-muted-foreground">Sin descripción.</span>}
                </p>
                <p className="mt-2 text-xs text-muted-foreground">
                    Creada el {formatearFecha(tarea.created_at)} por {tarea.creador.name}
                </p>
            </div>

            {tarea.estado === 'cancelada' && tarea.motivo_cancelacion && (
                <div className="border-b border-border py-4">
                    <p className={cn('text-sm text-foreground')}>Motivo de cancelación: {tarea.motivo_cancelacion}</p>
                </div>
            )}

            {(permisos.puedeUsarChecklistPersonal || tarea.colaboradores.length > 0) && (
                <div className="grid gap-6 border-b border-border py-4 sm:grid-cols-2 sm:items-start">
                    {permisos.puedeUsarChecklistPersonal && (
                        <div>
                            <h3 className="mb-3 flex items-center gap-2 text-sm font-semibold text-foreground">
                                <ListTodo className="size-4 text-verde-6" /> Mi checklist
                            </h3>
                            <ChecklistPersonalSection tareaId={tarea.id} items={checklistPersonal} puedeUsar={permisos.puedeUsarChecklistPersonal} />
                        </div>
                    )}

                    {tarea.colaboradores.length > 0 && (
                        <div>
                            <h3 className="mb-3 flex items-center gap-2 text-sm font-semibold text-foreground">
                                <ListTree className="size-4 text-verde-6" /> Subtareas
                            </h3>
                            <ChecklistSection
                                tareaId={tarea.id}
                                items={tarea.checklist_items}
                                personasElegibles={[tarea.responsable, ...tarea.colaboradores]}
                                puedeUsar={permisos.puedeUsarChecklist}
                                puedeAsignarDueno={permisos.puedeAsignarDuenoChecklist}
                            />
                        </div>
                    )}
                </div>
            )}

            <div className="border-b border-border py-4">
                <h3 className="mb-3 flex items-center gap-2 text-sm font-semibold text-foreground">
                    <GitBranch className="size-4 text-verde-6" /> Dependencias
                </h3>
                <DependenciasSection
                    tareaId={tarea.id}
                    tareasHijas={tarea.tareas_hijas}
                    personas={usuarios}
                    puedeCrear={permisos.puedeCrearTareaHija}
                />
            </div>

            <div className="border-b border-border py-4">
                <h3 className="mb-3 flex items-center gap-2 text-sm font-semibold text-foreground">
                    <Paperclip className="size-4 text-verde-6" /> Adjuntos
                </h3>
                <AdjuntosSection
                    tareaId={tarea.id}
                    adjuntos={tarea.adjuntos}
                    adjuntosDeTareasHijas={adjuntosDeTareasHijas}
                    puedeAdjuntar={permisos.puedeAdjuntar}
                />
            </div>

            {/* Actividad: historial integrado al pie, siempre visible -- el boton
                de la barra de arriba hace scroll hasta aca para que se note que
                existe sin depender de que alguien baje toda la pagina primero. */}
            <div ref={actividadRef} className="scroll-mt-4 pt-4">
                <HistorialInline eventos={tarea.historial} usuarios={usuarios} proyectos={proyectos} secciones={secciones} />
            </div>
        </div>
    );
}
