import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { useForm } from '@inertiajs/react';
import { CircleCheckBig } from 'lucide-react';
import { useState } from 'react';

/** RF-11: marcar completada no pide motivo, solo confirmacion. */
export function ConfirmarCompletarDialog({ trigger, tareaId }: { trigger: React.ReactNode; tareaId: number }) {
    const [open, setOpen] = useState(false);
    const { patch, processing } = useForm();

    const confirmar = () => {
        patch(route('tareas.completar', tareaId), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Marcar tarea como completada</DialogTitle>
                    <DialogDescription>Esta acción no se puede deshacer. ¿Confirmás que la tarea está terminada?</DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button onClick={confirmar} disabled={processing}>
                        <CircleCheckBig /> Completar tarea
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
