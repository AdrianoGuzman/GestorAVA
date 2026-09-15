import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Seccion } from '@/types/proyecto';
import { useForm } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

export function EditarSeccionDialog({ seccion }: { seccion: Seccion }) {
    const [open, setOpen] = useState(false);
    const { data, setData, patch, processing, errors } = useForm({
        nombre: seccion.nombre,
        peso: String(seccion.peso),
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('secciones.update', seccion.id), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <button
                    type="button"
                    className="rounded-full p-1 -m-1 text-verde-6 transition-colors hover:bg-verde-1 hover:text-verde-6 active:bg-verde-2"
                    title="Editar sección"
                >
                    <Pencil className="size-3.5" />
                </button>
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>Editar sección</DialogTitle>
                        <DialogDescription>Cambiar el nombre o el peso de esta sección dentro del proyecto.</DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="edit-seccion-nombre">Nombre</Label>
                            <Input id="edit-seccion-nombre" value={data.nombre} onChange={(e) => setData('nombre', e.target.value)} required />
                            {errors.nombre && <p className="text-sm text-rojo-1">{errors.nombre}</p>}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="edit-seccion-peso">Peso (0 a 1)</Label>
                            <Input
                                id="edit-seccion-peso"
                                type="number"
                                min={0}
                                max={1}
                                step={0.05}
                                value={data.peso}
                                onChange={(e) => setData('peso', e.target.value)}
                                required
                            />
                            {errors.peso && <p className="text-sm text-rojo-1">{errors.peso}</p>}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={processing || data.nombre.trim() === '' || data.peso === ''}>
                            Guardar cambios
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
