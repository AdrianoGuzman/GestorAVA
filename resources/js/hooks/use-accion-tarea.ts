import { TareaModalContext } from '@/components/tareas/tarea-modal-context';
import { toast } from '@/hooks/use-toast';
import { router } from '@inertiajs/react';
import axios, { AxiosError } from 'axios';
import { useContext, useState } from 'react';

type Metodo = 'post' | 'patch' | 'put' | 'delete';

interface OpcionesAccionTarea {
    onSuccess?: () => void;
    onError?: (errors: Record<string, string>) => void;
    onStart?: () => void;
    preserveScroll?: boolean;
    /** Necesario del lado de Inertia para mandar un FormData (subida de archivos). */
    forceFormData?: boolean;
    /**
     * Sin toast de exito -- para acciones de alta frecuencia (marcar un item
     * de checklist, agregar/editar/eliminar un paso) donde un toast en cada
     * click seria puro ruido. El resto de las acciones (crear/editar tarea,
     * reasignar, completar, cancelar, etc.) sí lo muestran por defecto.
     */
    silencioso?: boolean;
}

type Datos = Record<string, unknown> | FormData;

function aplanarErrores(errores: Record<string, string[] | string>): Record<string, string> {
    return Object.fromEntries(Object.entries(errores).map(([campo, mensaje]) => [campo, Array.isArray(mensaje) ? mensaje[0] : mensaje]));
}

/**
 * Envia una accion de tarea (completar, reasignar, marcar un item de
 * checklist, etc.) de la forma correcta segun donde se este usando -- ver
 * TareaModalContext para el motivo completo:
 *
 * - Dentro del modal de "Mis tareas": pide JSON directo por axios, sin
 *   pasar por el router de Inertia, y al terminar llama a `refrescar()` del
 *   contexto para traer el detalle actualizado con una sola peticion mas.
 * - En la pagina completa (`/tareas/{id}`, sin el contexto): sigue usando
 *   el router de Inertia tal cual se usaba antes, sin cambios de
 *   comportamiento (ahi back() ya es eficiente).
 *
 * Los errores de validacion (422) quedan disponibles igual en ambos modos
 * como `errors: Record<string, string>`, para que los formularios que usan
 * este hook no necesiten saber en cual de los dos estan corriendo.
 */
export function useAccionTarea() {
    const contexto = useContext(TareaModalContext);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const enviar = (metodo: Metodo, url: string, datos: Datos = {}, opciones: OpcionesAccionTarea = {}) => {
        setErrors({});
        opciones.onStart?.();

        if (!contexto) {
            setProcessing(true);
            router[metodo](url, datos as never, {
                preserveScroll: opciones.preserveScroll ?? true,
                forceFormData: opciones.forceFormData,
                onSuccess: (page) => {
                    const mensaje = (page.props as { flash?: { success?: string | null } }).flash?.success;
                    if (!opciones.silencioso && mensaje) {
                        toast({ variant: 'success', title: mensaje });
                    }
                    opciones.onSuccess?.();
                },
                onError: (erroresRecibidos) => {
                    const planos = erroresRecibidos as Record<string, string>;
                    setErrors(planos);
                    opciones.onError?.(planos);
                },
                onFinish: () => setProcessing(false),
            });
            return;
        }

        setProcessing(true);

        const config = { headers: { Accept: 'application/json' } };
        const peticion = metodo === 'delete' ? axios.delete(url, { ...config, data: datos }) : axios[metodo](url, datos, config);

        peticion
            // Esperamos a que termine el refetch antes de avisar el exito (y
            // de que el caller suelte su propio estado optimista, si tiene):
            // si soltaramos el optimismo apenas termina la mutacion, por un
            // instante se veria el dato VIEJO (el refetch todavia no llego),
            // y el checkbox parpadearia marcado -> desmarcado -> marcado.
            .then((respuesta) => contexto.refrescar().then(() => respuesta.data?.message as string | undefined))
            .then((mensaje) => {
                if (!opciones.silencioso && mensaje) {
                    toast({ variant: 'success', title: mensaje });
                }
                opciones.onSuccess?.();
            })
            .catch((error: AxiosError<{ message?: string; errors?: Record<string, string[]> }>) => {
                // onError se llama SIEMPRE que la accion no se concreto (422 de
                // validacion, 403 de permiso, error de red, etc.) -- si no,
                // callers que revierten un estado optimista (ej. el checklist)
                // se quedarian pegados en el valor equivocado para siempre
                // cuando el rechazo no es una validacion de formulario.
                const erroresValidacion = error.response?.data?.errors;
                const planos = erroresValidacion ? aplanarErrores(erroresValidacion) : {};
                setErrors(planos);

                // Ningun rechazo dentro del modal tenia una via garantizada de
                // mostrarse: un 403 sin campo (PermisoDenegadoException) no
                // tenia ningun toast (fuera del modal lo agarra FlashToaster
                // via flash.error, pero esta rama usa axios directo y nunca
                // llega ahi); y un 422 de validacion solo se veia si el
                // formulario que llama a este hook rendereaba justo ESE campo
                // (ej. reasignar-dialog.tsx no tiene cajita de error para
                // "mantener_como_colaborador"/"es_excepcion" -- el 422 llegaba
                // pero quedaba invisible, indistinguible de "no paso nada").
                // Mostrar siempre algo visible, sea cual sea la forma del
                // rechazo.
                const primerErrorValidacion = Object.values(planos)[0];
                const mensajeError = primerErrorValidacion ?? error.response?.data?.message;
                if (mensajeError) {
                    toast({ variant: 'destructive', title: mensajeError });
                }

                opciones.onError?.(planos);
            })
            .finally(() => setProcessing(false));
    };

    return { enviar, processing, errors, setErrors, enModal: contexto !== null };
}
