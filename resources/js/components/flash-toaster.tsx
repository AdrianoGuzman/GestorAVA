import { toast } from '@/hooks/use-toast';
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';

/**
 * PermisoDenegadoException::render() (ver bootstrap del backend) flashea
 * "error" via back()->with() para cualquier accion rechazada que no pasa por
 * un campo de formulario -- antes ese mensaje quedaba en la sesion sin que
 * ninguna vista lo mostrara nunca. Es poco frecuente (una accion bloqueada,
 * no un click de todos los dias) asi que un listener global no genera ruido,
 * a diferencia de "success" -- ver useAccionTarea, que lo maneja al vuelo de
 * cada peticion en vez de un listener reactivo, justamente para poder
 * silenciarlo en acciones de alta frecuencia (marcar un item de checklist).
 */
export function FlashToaster() {
    const { flash } = usePage<SharedData>().props;

    useEffect(() => {
        if (flash.error) {
            toast({ variant: 'destructive', title: flash.error });
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [flash.error]);

    return null;
}
