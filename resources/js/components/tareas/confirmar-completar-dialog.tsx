import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { useAccionTarea } from '@/hooks/use-accion-tarea';
import { CircleCheckBig } from 'lucide-react';
import { useState } from 'react';

/**
 * RF-11: marcar completada no pide motivo, solo confirmacion. Sin campos de
 * formulario propios -- pero FinalizacionService puede rechazar la accion
 * igual (RF-22 dependencias pendientes, RF-23 subtareas sin marcar, o un
 * estado que ya no admite completar), con el motivo bajo la clave "bloqueos"
 * (o "estado"), no ligada a ningun input. Sin mostrarlo aca explicitamente,
 * el boton "no hacia nada" a los ojos de quien lo aprieta: la tarea seguia
 * sin completarse y no habia ninguna pista de por que.
 */
export function ConfirmarCompletarDialog({ trigger, tareaId }: { trigger: React.ReactNode; tareaId: number }) {
    const [open, setOpen] = useState(false);
    const { enviar, processing, errors } = useAccionTarea();
    const bloqueo = errors.bloqueos ?? errors.estado;

    const confirmar = () => {
        enviar('patch', route('tareas.completar', tareaId), {}, { onSuccess: () => setOpen(false) });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Marcar tarea como completada</DialogTitle>
                    <DialogDescription>Esta acción no se puede deshacer. ¿Confirmás que la tarea está terminada?</DialogDescription>
                </DialogHeader>
                {bloqueo && <p className="text-sm text-rojo-1">{bloqueo}</p>}
                <DialogFooter>
                    <Button onClick={confirmar} disabled={processing}>
                        <CircleCheckBig /> Completar tarea
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
