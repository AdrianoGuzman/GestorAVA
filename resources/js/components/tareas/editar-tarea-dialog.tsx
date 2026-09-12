import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

/**
 * Editar titulo/descripcion/fechas de una tarea ya creada -- antes de esto
 * no habia forma de corregir un error de tipeo sin cancelar y crear de
 * nuevo. No toca responsable/colaboradores (eso ya tiene su propio flujo).
 */
export function EditarTareaDialog({
    trigger,
    tareaId,
    titulo,
    descripcion,
    fechaInicio,
    fechaCompromiso,
}: {
    trigger: React.ReactNode;
    tareaId: number;
    titulo: string;
    descripcion: string | null;
    fechaInicio: string | null;
    fechaCompromiso: string;
}) {
    const [open, setOpen] = useState(false);
    const { data, setData, patch, processing, errors, reset } = useForm({
        titulo,
        descripcion: descripcion ?? '',
        fecha_inicio: fechaInicio ? fechaInicio.slice(0, 10) : '',
        fecha_compromiso: fechaCompromiso.slice(0, 10),
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('tareas.actualizar', tareaId), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
            onError: () => reset('titulo', 'descripcion', 'fecha_inicio', 'fecha_compromiso'),
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
                                <Label htmlFor="edit-fecha_inicio">Fecha inicio (opcional)</Label>
                                <Input
                                    id="edit-fecha_inicio"
                                    type="date"
                                    value={data.fecha_inicio}
                                    onChange={(e) => setData('fecha_inicio', e.target.value)}
                                />
                                {errors.fecha_inicio && <p className="text-sm text-rojo-1">{errors.fecha_inicio}</p>}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="edit-fecha_compromiso">Fecha término</Label>
                                <Input
                                    id="edit-fecha_compromiso"
                                    type="date"
                                    value={data.fecha_compromiso}
                                    onChange={(e) => setData('fecha_compromiso', e.target.value)}
                                    required
                                />
                                {errors.fecha_compromiso && <p className="text-sm text-rojo-1">{errors.fecha_compromiso}</p>}
                            </div>
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
