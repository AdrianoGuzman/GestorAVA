import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/react';
import { Copy } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

/**
 * RF-18 (Could): copia responsable y unidad organizacional de la tarea
 * origen (no editables aca); titulo, descripcion y fechas quedan editables,
 * prellenados con los valores de la tarea origen como punto de partida.
 */
export function DuplicarTareaDialog({
    tareaId,
    titulo,
    descripcion,
    fechaInicio,
    fechaCompromiso,
}: {
    tareaId: number;
    titulo: string;
    descripcion: string | null;
    fechaInicio: string | null;
    fechaCompromiso: string;
}) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        titulo,
        descripcion: descripcion ?? '',
        fecha_inicio: fechaInicio ?? '',
        fecha_compromiso: fechaCompromiso,
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
            <DialogTrigger asChild>
                <Button size="sm" variant="outline">
                    <Copy /> Duplicar
                </Button>
            </DialogTrigger>
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
                                    value={data.fecha_compromiso}
                                    onChange={(e) => setData('fecha_compromiso', e.target.value)}
                                    required
                                />
                                {errors.fecha_compromiso && <p className="text-sm text-rojo-1">{errors.fecha_compromiso}</p>}
                            </div>
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
