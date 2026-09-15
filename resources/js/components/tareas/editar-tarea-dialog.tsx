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
import { Pencil } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

/**
 * Editar titulo/descripcion/fechas/prioridad de una tarea ya creada -- antes
 * de esto no habia forma de corregir un error de tipeo sin cancelar y crear
 * de nuevo. No toca responsable/colaboradores (eso ya tiene su propio flujo).
 */
/** Sentinel para "sin proyecto"/"sin sección" en los Select -- Radix no acepta value="". */
const SIN_PROYECTO = 'sin-proyecto';
const SIN_SECCION = 'sin-seccion';

export function EditarTareaDialog({
    trigger,
    tareaId,
    titulo,
    descripcion,
    fechaInicio,
    fechaCompromiso,
    prioridad,
    proyectoId,
    seccionId,
    proyectos,
    secciones,
}: {
    trigger: React.ReactNode;
    tareaId: number;
    titulo: string;
    descripcion: string | null;
    fechaInicio: string | null;
    fechaCompromiso: string;
    prioridad: PrioridadTarea;
    proyectoId: number | null;
    seccionId: number | null;
    proyectos: ProyectoResumen[];
    secciones: SeccionResumen[];
}) {
    const { auth } = usePage<SharedData>().props;
    const [open, setOpen] = useState(false);
    const { data, setData, reset } = useForm({
        titulo,
        descripcion: descripcion ?? '',
        fecha_inicio: fechaInicio ? fechaInicio.slice(0, 10) : '',
        fecha_compromiso: fechaCompromiso.slice(0, 10),
        prioridad,
        proyecto_id: proyectoId,
        seccion_id: seccionId,
    });
    const { enviar, processing, errors } = useAccionTarea();
    const seccionesDelProyecto = secciones.filter((seccion) => seccion.proyecto_id === data.proyecto_id);

    // RN (14-09-2026): toda tarea de un proyecto debe caer dentro de su
    // plazo. El backend lo exige igual (defensa real); esto solo evita
    // elegir una fecha invalida en el calendario.
    const proyectoSeleccionado = proyectos.find((proyecto) => proyecto.id === data.proyecto_id);
    const proyectoFechaInicio = proyectoSeleccionado?.fecha_inicio?.slice(0, 10);
    const proyectoFechaTermino = proyectoSeleccionado?.fecha_termino?.slice(0, 10);
    const minFechaCompromiso = [data.fecha_inicio, proyectoFechaInicio].filter(Boolean).sort().pop() || undefined;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        enviar('patch', route('tareas.actualizar', tareaId), data, {
            onSuccess: () => setOpen(false),
            onError: () => reset('titulo', 'descripcion', 'fecha_inicio', 'fecha_compromiso', 'prioridad', 'proyecto_id', 'seccion_id'),
        });
    };

    return (
        // modal={false}: desde que las fechas usan DatePickerButton (un
        // Popover), este dialogo necesita el mismo modo no-modal que
        // crear-tarea-dialog.tsx -- anidado dentro del modal grande de
        // detalle de tarea, el focus-trap/scroll-lock de un Dialog modal de
        // por medio deja el Popover visible pero inerte (no abre o no
        // responde a clicks). NonModalOverlay repone el fondo difuminado que
        // Radix deja de pintar en ese modo.
        <>
            <NonModalOverlay open={open} onClose={() => setOpen(false)} />
            <Dialog open={open} onOpenChange={setOpen} modal={false}>
                <DialogTrigger asChild>{trigger}</DialogTrigger>
                <DialogContent>
                    <form onSubmit={submit}>
                        <DialogHeader>
                            <DialogTitle>Editar tarea</DialogTitle>
                            <DialogDescription>Corregí el título, la descripción o las fechas. No cambia responsable ni colaboradores.</DialogDescription>
                        </DialogHeader>

                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="edit-titulo">Título</Label>
                                <Input id="edit-titulo" value={data.titulo} onChange={(e) => setData('titulo', e.target.value)} required />
                                {errors.titulo && <p className="text-sm text-rojo-1">{errors.titulo}</p>}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="edit-descripcion">Descripción</Label>
                                <Textarea
                                    id="edit-descripcion"
                                    value={data.descripcion}
                                    onChange={(e) => setData('descripcion', e.target.value)}
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
                                        minFecha={minFechaCompromiso}
                                        maxFecha={proyectoFechaTermino}
                                        className="h-10 w-full justify-start text-sm"
                                    />
                                    {errors.fecha_compromiso && <p className="text-sm text-rojo-1">{errors.fecha_compromiso}</p>}
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="edit-prioridad">Prioridad</Label>
                                <Select value={data.prioridad} onValueChange={(valor) => setData('prioridad', valor as PrioridadTarea)}>
                                    <SelectTrigger id="edit-prioridad">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {PRIORIDADES_ORDENADAS.map((opcion) => (
                                            <SelectItem key={opcion} value={opcion}>
                                                {PRIORIDAD_TAREA_LABELS[opcion]}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.prioridad && <p className="text-sm text-rojo-1">{errors.prioridad}</p>}
                            </div>

                            {auth.puedeAdministrarProyectos && (
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="edit-proyecto">Proyecto (opcional)</Label>
                                        <Select
                                            value={data.proyecto_id === null ? SIN_PROYECTO : String(data.proyecto_id)}
                                            onValueChange={(valor) => {
                                                setData('proyecto_id', valor === SIN_PROYECTO ? null : Number(valor));
                                                setData('seccion_id', null);
                                            }}
                                        >
                                            <SelectTrigger id="edit-proyecto">
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
                                            <Label htmlFor="edit-seccion">Sección (opcional)</Label>
                                            <Select
                                                value={data.seccion_id === null ? SIN_SECCION : String(data.seccion_id)}
                                                onValueChange={(valor) => setData('seccion_id', valor === SIN_SECCION ? null : Number(valor))}
                                            >
                                                <SelectTrigger id="edit-seccion">
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
                        </div>

                        <DialogFooter>
                            <Button type="submit" disabled={processing || data.titulo.trim() === '' || data.fecha_compromiso === ''}>
                                <Pencil /> Guardar cambios
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
