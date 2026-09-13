import { Button } from '@/components/ui/button';
import { DatePickerButton } from '@/components/ui/date-picker-button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useAccionTarea } from '@/hooks/use-accion-tarea';
import { PRIORIDAD_TAREA_LABELS, PRIORIDADES_ORDENADAS } from '@/lib/estado-tarea';
import type { PrioridadTarea } from '@/types/tarea';
import { useForm } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

/**
 * Editar titulo/descripcion/fechas/prioridad de una tarea ya creada -- antes
 * de esto no habia forma de corregir un error de tipeo sin cancelar y crear
 * de nuevo. No toca responsable/colaboradores (eso ya tiene su propio flujo).
 */
export function EditarTareaDialog({
    trigger,
    tareaId,
    titulo,
    descripcion,
    fechaInicio,
    fechaCompromiso,
    prioridad,
}: {
    trigger: React.ReactNode;
    tareaId: number;
    titulo: string;
    descripcion: string | null;
    fechaInicio: string | null;
    fechaCompromiso: string;
    prioridad: PrioridadTarea;
}) {
    const [open, setOpen] = useState(false);
    const { data, setData, reset } = useForm({
        titulo,
        descripcion: descripcion ?? '',
        fecha_inicio: fechaInicio ? fechaInicio.slice(0, 10) : '',
        fecha_compromiso: fechaCompromiso.slice(0, 10),
        prioridad,
    });
    const { enviar, processing, errors } = useAccionTarea();

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        enviar('patch', route('tareas.actualizar', tareaId), data, {
            onSuccess: () => setOpen(false),
            onError: () => reset('titulo', 'descripcion', 'fecha_inicio', 'fecha_compromiso', 'prioridad'),
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
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
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={processing || data.titulo.trim() === '' || data.fecha_compromiso === ''}>
                            <Pencil /> Guardar cambios
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
