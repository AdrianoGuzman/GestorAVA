import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import type { UsuarioAdmin } from '@/types/usuario';
import { useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

/** Baja de usuario (RNF-08): solo Directorio, ver NivelJerarquico::puedeEliminarUsuarios(). */
export function EliminarUsuarioDialog({ usuario }: { usuario: UsuarioAdmin }) {
    const [open, setOpen] = useState(false);
    const { delete: destroy, processing, errors } = useForm();

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        destroy(route('usuarios.destroy', usuario.id), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="destructive" size="sm">
                    <Trash2 /> Eliminar
                </Button>
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>¿Eliminar a {usuario.name}?</DialogTitle>
                        <DialogDescription>
                            Esta accion no se puede deshacer. Si el usuario tiene tareas asociadas como responsable o creador, no podra eliminarse.
                        </DialogDescription>
                    </DialogHeader>

                    {errors.usuario && <p className="text-sm text-rojo-1">{errors.usuario}</p>}

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button variant="secondary">Cancelar</Button>
                        </DialogClose>
                        <Button variant="destructive" disabled={processing} asChild>
                            <button type="submit">Eliminar</button>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
