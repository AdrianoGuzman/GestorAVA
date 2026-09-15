import { PersonaPicker, type Persona } from '@/components/tareas/persona-picker';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DatePickerButton } from '@/components/ui/date-picker-button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger, NonModalOverlay } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useAccionTarea } from '@/hooks/use-accion-tarea';
import { PRIORIDAD_TAREA_LABELS, PRIORIDADES_ORDENADAS } from '@/lib/estado-tarea';
import type { SharedData } from '@/types';
import type { PrioridadTarea, ProyectoResumen, SeccionResumen } from '@/types/tarea';
import { useForm, usePage } from '@inertiajs/react';
import { Plus, UserPlus, X } from 'lucide-react';
import { FormEventHandler, useEffect, useState } from 'react';

/**
 * RF-04: crear una tarea nueva. El creador queda como responsable salvo que
 * elija a otra persona.
 *
 * `open`/`onOpenChange` son opcionales: sin ellos el dialog maneja su propio
 * estado a partir del `trigger` (uso normal, ej. boton "Nueva tarea"). Se
 * pasan cuando algo externo necesita abrirlo sin un trigger propio -- ej. el
 * calendario, que lo abre al hacer clic en un dia sin tareas, precargando
 * `fechaCompromisoInicial` con esa fecha.
 *
 * `tareaPadreId` (RF-21): si se pasa, esto crea una tarea hija de esa tarea
 * en vez de una tarea normal -- mismo formulario, solo cambia el endpoint y
 * el texto del dialog.
 */
/** Sentinel para "sin proyecto"/"sin sección" en los Select -- Radix no acepta value="". */
const SIN_PROYECTO = 'sin-proyecto';
const SIN_SECCION = 'sin-seccion';

export function CrearTareaDialog({
    trigger,
    personas,
    proyectos = [],
    secciones = [],
    proyectoIdInicial,
    fechaCompromisoInicial,
    tareaPadreId,
    open: openControlado,
    onOpenChange,
}: {
    trigger?: React.ReactNode;
    personas: Persona[];
    proyectos?: ProyectoResumen[];
    secciones?: SeccionResumen[];
    /** Preselecciona el proyecto al abrir -- usado desde "Mis proyectos" (ver proyectos/index.tsx). */
    proyectoIdInicial?: number;
    fechaCompromisoInicial?: string;
    tareaPadreId?: number;
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
}) {
    const { auth } = usePage<SharedData>().props;
    const [openInterno, setOpenInterno] = useState(false);
    const open = openControlado ?? openInterno;
    const setOpen = onOpenChange ?? setOpenInterno;
    const [responsable, setResponsable] = useState<Persona | null>(null);
    const [colaboradores, setColaboradores] = useState<Persona[]>([]);
    const { enviar, processing, errors } = useAccionTarea();
    const { data, setData, reset } = useForm({
        titulo: '',
        descripcion: '',
        fecha_inicio: '',
        fecha_compromiso: fechaCompromisoInicial ?? '',
        prioridad: 'media' as PrioridadTarea,
        responsable_id: '',
        colaboradores: [] as number[],
        proyecto_id: null as number | null,
        seccion_id: null as number | null,
    });
    const seccionesDelProyecto = secciones.filter((seccion) => seccion.proyecto_id === data.proyecto_id);

    // RN (14-09-2026): toda tarea de un proyecto debe caer dentro de su plazo.
    // El backend lo exige igual (defensa real); esto solo evita elegir una
    // fecha invalida en el calendario para no tener que corregirla despues.
    const proyectoSeleccionado = proyectos.find((proyecto) => proyecto.id === data.proyecto_id);
    const proyectoFechaInicio = proyectoSeleccionado?.fecha_inicio?.slice(0, 10);
    const proyectoFechaTermino = proyectoSeleccionado?.fecha_termino?.slice(0, 10);
    const minFechaCompromiso = [data.fecha_inicio, proyectoFechaInicio].filter(Boolean).sort().pop() || undefined;

    useEffect(() => {
        if (!open) return;

        if (!responsable) {
            const yoMismo = personas.find((p) => p.id === auth.user.id) ?? { id: auth.user.id, name: auth.user.name, email: auth.user.email };
            setResponsable(yoMismo);
            setData('responsable_id', String(yoMismo.id));
        }

        if (fechaCompromisoInicial) {
            setData('fecha_compromiso', fechaCompromisoInicial);
        }

        if (proyectoIdInicial !== undefined) {
            setData('proyecto_id', proyectoIdInicial);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open]);

    const alternarColaborador = (persona: Persona) => {
        setColaboradores((actual) => {
            const siguiente = actual.some((p) => p.id === persona.id) ? actual.filter((p) => p.id !== persona.id) : [...actual, persona];
            setData('colaboradores', siguiente.map((p) => p.id));
            return siguiente;
        });
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        enviar('post', tareaPadreId ? route('tareas.hijas.store', tareaPadreId) : route('tareas.store'), data, {
            onSuccess: () => {
                setOpen(false);
                setResponsable(null);
                setColaboradores([]);
                reset();
            },
        });
    };

    return (
        <>
            <NonModalOverlay open={open} onClose={() => setOpen(false)} />

            {/* modal={false}: adentro se abre un Popover (PersonaPicker) para elegir
                responsable/colaboradores -- con el modal atrapando el foco, ese
                Popover queda visible pero inerte (no se puede elegir a nadie). Ver
                el mismo patron en tarea-detalle-modal.tsx. */}
            <Dialog open={open} onOpenChange={setOpen} modal={false}>
                {trigger && <DialogTrigger asChild>{trigger}</DialogTrigger>}
                <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>{tareaPadreId ? 'Nueva tarea hija' : 'Nueva tarea'}</DialogTitle>
                        <DialogDescription>Por defecto quedas como responsable, salvo que elijas a otra persona.</DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="titulo">Título</Label>
                            <Input id="titulo" value={data.titulo} onChange={(e) => setData('titulo', e.target.value)} required />
                            {errors.titulo && <p className="text-sm text-rojo-1">{errors.titulo}</p>}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="descripcion">Descripción</Label>
                            <Textarea
                                id="descripcion"
                                value={data.descripcion}
                                onChange={(e) => setData('descripcion', e.target.value)}
                                className="max-h-40 overflow-y-auto"
                            />
                            {errors.descripcion && <p className="text-sm text-rojo-1">{errors.descripcion}</p>}
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label>Fecha inicio (opcional)</Label>
                                <DatePickerButton
                                    label="Elegir fecha"
                                    valor={data.fecha_inicio}
                                    onChange={(valor) => setData('fecha_inicio', valor)}
                                    minFecha={proyectoFechaInicio}
                                    maxFecha={proyectoFechaTermino}
                                    className="h-10 w-full justify-start text-sm"
                                />
                                {errors.fecha_inicio && <p className="text-sm text-rojo-1">{errors.fecha_inicio}</p>}
                            </div>

                            <div className="grid gap-2">
                                <Label>Fecha término</Label>
                                <DatePickerButton
                                    label="Elegir fecha"
                                    valor={data.fecha_compromiso}
                                    onChange={(valor) => setData('fecha_compromiso', valor)}
                                    soloFuturo
                                    minFecha={minFechaCompromiso}
                                    maxFecha={proyectoFechaTermino}
                                    className="h-10 w-full justify-start text-sm"
                                />
                                {errors.fecha_compromiso && <p className="text-sm text-rojo-1">{errors.fecha_compromiso}</p>}
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="prioridad">Prioridad</Label>
                            <Select value={data.prioridad} onValueChange={(valor) => setData('prioridad', valor as PrioridadTarea)}>
                                <SelectTrigger id="prioridad">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {PRIORIDADES_ORDENADAS.map((prioridad) => (
                                        <SelectItem key={prioridad} value={prioridad}>
                                            {PRIORIDAD_TAREA_LABELS[prioridad]}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.prioridad && <p className="text-sm text-rojo-1">{errors.prioridad}</p>}
                        </div>

                        {auth.puedeAdministrarProyectos && proyectos.length > 0 && (
                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="proyecto">Proyecto (opcional)</Label>
                                    <Select
                                        value={data.proyecto_id === null ? SIN_PROYECTO : String(data.proyecto_id)}
                                        onValueChange={(valor) => {
                                            setData('proyecto_id', valor === SIN_PROYECTO ? null : Number(valor));
                                            setData('seccion_id', null);
                                        }}
                                    >
                                        <SelectTrigger id="proyecto">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={SIN_PROYECTO}>Sin proyecto</SelectItem>
                                            {proyectos.map((proyecto) => (
                                                <SelectItem key={proyecto.id} value={String(proyecto.id)}>
                                                    {proyecto.nombre}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.proyecto_id && <p className="text-sm text-rojo-1">{errors.proyecto_id}</p>}
                                </div>

                                {data.proyecto_id !== null && seccionesDelProyecto.length > 0 && (
                                    <div className="grid gap-2">
                                        <Label htmlFor="seccion">Sección (opcional)</Label>
                                        <Select
                                            value={data.seccion_id === null ? SIN_SECCION : String(data.seccion_id)}
                                            onValueChange={(valor) => setData('seccion_id', valor === SIN_SECCION ? null : Number(valor))}
                                        >
                                            <SelectTrigger id="seccion">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value={SIN_SECCION}>Sin sección</SelectItem>
                                                {seccionesDelProyecto.map((seccion) => (
                                                    <SelectItem key={seccion.id} value={String(seccion.id)}>
                                                        {seccion.nombre}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.seccion_id && <p className="text-sm text-rojo-1">{errors.seccion_id}</p>}
                                    </div>
                                )}
                            </div>
                        )}

                        <div className="grid gap-2">
                            <Label>Responsable</Label>
                            <PersonaPicker
                                personas={personas}
                                seleccionadosIds={responsable ? [responsable.id] : []}
                                cerrarAlSeleccionar
                                side="top"
                                onSelect={(persona) => {
                                    setResponsable(persona);
                                    setData('responsable_id', String(persona.id));
                                }}
                                trigger={
                                    <button
                                        type="button"
                                        className="flex w-full items-center gap-2 rounded-md border border-input bg-background px-3 py-2 text-left text-sm hover:bg-verde-1 hover:text-gris-2"
                                    >
                                        {responsable ? (
                                            <>
                                                <Avatar className="size-6">
                                                    <AvatarFallback className="bg-gris-2 text-[10px] text-white">
                                                        {responsable.name.slice(0, 2).toUpperCase()}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <span>{responsable.name}</span>
                                            </>
                                        ) : (
                                            <span className="text-muted-foreground">Elegir persona...</span>
                                        )}
                                    </button>
                                }
                            />
                            {errors.responsable_id && <p className="text-sm text-rojo-1">{errors.responsable_id}</p>}
                        </div>

                        <div className="grid gap-2">
                            <Label>Colaboradores (opcional)</Label>

                            {colaboradores.length > 0 && (
                                <div className="flex flex-wrap gap-1.5">
                                    {colaboradores.map((persona) => (
                                        <span
                                            key={persona.id}
                                            className="inline-flex items-center gap-1 rounded-full bg-verde-2 py-1 pl-2.5 pr-1 text-xs font-medium text-gris-2"
                                        >
                                            {persona.name}
                                            <button
                                                type="button"
                                                onClick={() => alternarColaborador(persona)}
                                                className="rounded-full hover:bg-verde-3"
                                            >
                                                <X className="size-3" />
                                            </button>
                                        </span>
                                    ))}
                                </div>
                            )}

                            <PersonaPicker
                                personas={personas}
                                seleccionadosIds={colaboradores.map((p) => p.id)}
                                onSelect={alternarColaborador}
                                cerrarAlSeleccionar
                                side="top"
                                trigger={
                                    <Button type="button" variant="outline" size="sm" className="w-fit">
                                        <UserPlus /> Agregar persona
                                    </Button>
                                }
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={processing || data.titulo.trim() === '' || data.fecha_compromiso === ''}>
                            <Plus /> {tareaPadreId ? 'Crear tarea hija' : 'Crear tarea'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
            </Dialog>
        </>
    );
}
