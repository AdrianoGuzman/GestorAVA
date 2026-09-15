import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

/** Alta de una Sección dentro de un Proyecto (agrupa tareas por objetivo, con un peso que pondera el avance del proyecto). */
export function CrearSeccionDialog({ proyectoId }: { proyectoId: number }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        nombre: '',
        peso: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('secciones.store', proyectoId), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button type="button" variant="outline" size="sm" className="w-fit">
                    <Plus /> Agregar sección
                </Button>
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>Nueva sección</DialogTitle>
                        <DialogDescription>
                            Agrupa tareas de este proyecto por objetivo (ej. "Objetivo 1: Cultura"). El peso pondera cuánto aporta al
                            avance total del proyecto.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="seccion-nombre">Nombre</Label>
                            <Input id="seccion-nombre" value={data.nombre} onChange={(e) => setData('nombre', e.target.value)} required />
                            {errors.nombre && <p className="text-sm text-rojo-1">{errors.nombre}</p>}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="seccion-peso">Peso (0 a 1)</Label>
                            <Input
                                id="seccion-peso"
                                type="number"
                                min={0}
                                max={1}
                                step={0.05}
                                value={data.peso}
                                onChange={(e) => setData('peso', e.target.value)}
                                placeholder="Ej. 0.4"
                                required
                            />
                            <p className="text-xs text-muted-foreground">
                                No hace falta que los pesos de todas las secciones sumen exactamente 1 -- se ajusta solo.
                            </p>
                            {errors.peso && <p className="text-sm text-rojo-1">{errors.peso}</p>}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={processing || data.nombre.trim() === '' || data.peso === ''}>
                            <Plus /> Crear sección
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
