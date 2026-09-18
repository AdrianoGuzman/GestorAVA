import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { useAccionTarea } from '@/hooks/use-accion-tarea';
import { toast } from '@/hooks/use-toast';
import { CircleCheckBig } from 'lucide-react';
import { useState } from 'react';

/**
 * RF-11: marcar completada no pide motivo, solo confirmacion. Sin campos de
 * formulario propios -- pero FinalizacionService puede rechazar la accion
 * igual (RF-22 dependencias pendientes, RF-23 subtareas sin marcar, o un
 * estado que ya no admite completar), con el motivo bajo la clave "bloqueos"
 * (o "estado"), no ligada a ningun input. Dentro del modal de "Mis tareas"
 * ese rechazo ya dispara un toast solo (ver useAccionTarea); en la pagina
 * completa (`/tareas/{id}`) no pasa por ahi, asi que lo disparamos a mano
 * para no dejar el boton "sin hacer nada" a los ojos de quien lo aprieta.
 */
export function ConfirmarCompletarDialog({ trigger, tareaId }: { trigger: React.ReactNode; tareaId: number }) {
    const [open, setOpen] = useState(false);
    const { enviar, processing, enModal } = useAccionTarea();

    const confirmar = () => {
        enviar('patch', route('tareas.completar', tareaId), {}, {
            onSuccess: () => setOpen(false),
            onError: (errores) => {
                if (!enModal) {
                    const mensaje = errores.bloqueos ?? errores.estado;
                    if (mensaje) toast({ variant: 'destructive', title: mensaje });
                }
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Marcar tarea como completada</DialogTitle>
                    <DialogDescription>Esta acción no se puede deshacer. ¿Confirmas que la tarea está terminada?</DialogDescription>
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
