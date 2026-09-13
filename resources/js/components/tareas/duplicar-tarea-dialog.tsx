import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { fechaMinimaCompromiso, PRIORIDAD_TAREA_LABELS, PRIORIDADES_ORDENADAS } from '@/lib/estado-tarea';
import type { PrioridadTarea } from '@/types/tarea';
import { useForm } from '@inertiajs/react';
import { Copy } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

/**
 * RF-18 (Could): copia responsable y unidad organizacional de la tarea
 * origen (no editables aca); titulo, descripcion, fechas y prioridad quedan
 * editables, prellenados con los valores de la tarea origen como punto de
 * partida.
 *
 * `trigger` es opcional: por defecto usa su propio boton "Duplicar", pero
 * si se omite el dialogo se abre solo via `open`/`onOpenChange`
 * controlados desde afuera -- lo usa el menu "Mas acciones" del detalle
 * de tarea (ver nota en MotivoDialog sobre por que no se dispara desde un
 * DialogTrigger dentro de un DropdownMenuItem).
 */
export function DuplicarTareaDialog({
    tareaId,
    titulo,
    descripcion,
    fechaInicio,
    fechaCompromiso,
    prioridad,
    trigger,
    open: openControlado,
    onOpenChange,
}: {
    tareaId: number;
    titulo: string;
    descripcion: string | null;
    fechaInicio: string | null;
    fechaCompromiso: string;
    prioridad: PrioridadTarea;
    trigger?: React.ReactNode;
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
}) {
    const [openInterno, setOpenInterno] = useState(false);
    const open = openControlado ?? openInterno;
    const setOpen = onOpenChange ?? setOpenInterno;
    const { data, setData, post, processing, errors, reset } = useForm({
        titulo,
        descripcion: descripcion ?? '',
        fecha_inicio: fechaInicio ?? '',
        fecha_compromiso: fechaCompromiso,
        prioridad,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('tareas.duplicar', tareaId), {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                reset();
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            {trigger && <DialogTrigger asChild>{trigger}</DialogTrigger>}
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>Duplicar tarea</DialogTitle>
                        <DialogDescription>
                            Se copian responsable y unidad organizacional. Ajustá título, descripción y fechas antes de crearla.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="titulo">Título</Label>
                            <Input id="titulo" value={data.titulo} onChange={(e) => setData('titulo', e.target.value)} required />
                            {errors.titulo && <p className="text-sm text-rojo-1">{errors.titulo}</p>}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="descripcion">Descripción</Label>
                            <Textarea id="descripcion" value={data.descripcion} onChange={(e) => setData('descripcion', e.target.value)} />
                            {errors.descripcion && <p className="text-sm text-rojo-1">{errors.descripcion}</p>}
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="fecha_inicio">Fecha inicio (opcional)</Label>
                                <Input
                                    id="fecha_inicio"
                                    type="date"
                                    value={data.fecha_inicio}
                                    onChange={(e) => setData('fecha_inicio', e.target.value)}
                                />
                                {errors.fecha_inicio && <p className="text-sm text-rojo-1">{errors.fecha_inicio}</p>}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="fecha_compromiso">Fecha término</Label>
                                <Input
                                    id="fecha_compromiso"
                                    type="date"
                                    min={fechaMinimaCompromiso()}
                                    value={data.fecha_compromiso}
                                    onChange={(e) => setData('fecha_compromiso', e.target.value)}
                                    required
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
                                    {PRIORIDADES_ORDENADAS.map((opcion) => (
                                        <SelectItem key={opcion} value={opcion}>
                                            {PRIORIDAD_TAREA_LABELS[opcion]}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.prioridad && <p className="text-sm text-rojo-1">{errors.prioridad}</p>}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            <Copy /> Duplicar tarea
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
