import { TareaDetalleContent, type TareaDetalleContentProps } from '@/components/tareas/tarea-detalle-content';
import { Dialog, DialogContent, DialogTitle, NonModalOverlay } from '@/components/ui/dialog';
import { router, usePage } from '@inertiajs/react';
import axios from 'axios';
import { Loader2 } from 'lucide-react';
import { useEffect, useState } from 'react';

/**
 * Pide la misma ruta de detalle (`/tareas/{id}`) pero con los headers que usa
 * Inertia por debajo -- el backend responde solo los props en JSON en vez de
 * la pagina completa, y los renderizamos dentro de un modal sin navegar
 * fuera de "Mis tareas" (el usuario no pierde los filtros ni el scroll).
 *
 * "X-Requested-With" hace que Laravel trate este GET como ajax y NO lo
 * guarde como "url anterior" de la sesion -- si no, el boton "Completar" (u
 * otra accion) dentro del modal terminaria devolviendo (`back()`) a
 * `/tareas/{id}` en vez de de vuelta a "Mis tareas".
 *
 * Si la version de assets no calza (409) o pasa cualquier otro error, se
 * cae a una navegacion normal de Inertia a la pagina completa -- nunca deja
 * al usuario sin poder ver la tarea.
 *
 * Ojo con las acciones de adentro (completar, agregar colaborador, marcar
 * un item del checklist, etc.): como nunca navegamos "de verdad" a
 * /tareas/{id}, Inertia sigue viendo "mis-tareas/index" como la pagina
 * actual -- cuando una accion termina bien y el backend redirige de vuelta
 * ahi, React re-renderiza ESE MISMO componente (no lo desmonta), asi que
 * nuestro estado local (los datos ya pedidos por axios) no se entera solo.
 * Por eso escuchamos el evento global "success" del router de Inertia y
 * volvemos a pedir los datos de la tarea abierta cada vez que una accion
 * termina -- sin mostrar el spinner grande, para que se sienta como una
 * actualizacion en vivo y no como que el modal se recarga.
 */
export function useTareaDetalleModal() {
    const { version } = usePage();
    const [tareaId, setTareaId] = useState<number | null>(null);
    const [datos, setDatos] = useState<TareaDetalleContentProps | null>(null);
    const [cargando, setCargando] = useState(false);

    const cargar = (id: number, mostrarSpinner: boolean) => {
        if (mostrarSpinner) setCargando(true);

        axios
            .get(`/tareas/${id}`, {
                headers: {
                    'X-Inertia': true,
                    'X-Inertia-Version': version ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'text/html, application/xhtml+xml',
                },
            })
            .then((response) => setDatos(response.data.props as TareaDetalleContentProps))
            .catch(() => {
                if (mostrarSpinner) router.visit(`/tareas/${id}`);
            })
            .finally(() => {
                if (mostrarSpinner) setCargando(false);
            });
    };

    const abrir = (id: number) => {
        setTareaId(id);
        setDatos(null);
        cargar(id, true);
    };

    const cerrar = () => {
        setTareaId(null);
        setDatos(null);
    };

    useEffect(() => {
        if (tareaId === null) return;

        return router.on('success', () => cargar(tareaId, false));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [tareaId]);

    return { tareaId, datos, cargando, abrir, cerrar };
}

export function TareaDetalleModal({
    tareaId,
    datos,
    cargando,
    onClose,
}: {
    tareaId: number | null;
    datos: TareaDetalleContentProps | null;
    cargando: boolean;
    onClose: () => void;
}) {
    return (
        <>
            <NonModalOverlay open={tareaId !== null} onClose={onClose} />

            {/* modal={false}: adentro se abren otros Dialog (Reasignar, Agregar
                colaborador) con su propio Popover de seleccion de persona -- con
                el modal de aca "atrapando" el foco/scroll, esos anidados quedan
                visibles pero no interactuables (no se puede hacer scroll ni
                elegir a nadie). Al desactivar el modal de este nivel, el Dialog
                interno vuelve a ser el unico que atrapa el foco y funciona bien. */}
            <Dialog open={tareaId !== null} onOpenChange={(open) => !open && onClose()} modal={false}>
                <DialogContent className="max-h-[90vh] max-w-6xl overflow-y-auto">
                    <DialogTitle className="sr-only">{datos?.tarea.titulo ?? 'Detalle de tarea'}</DialogTitle>
                    {cargando || !datos ? (
                        <div className="flex items-center justify-center gap-2 py-16 text-muted-foreground">
                            <Loader2 className="size-5 animate-spin" /> Cargando tarea...
                        </div>
                    ) : (
                        <TareaDetalleContent {...datos} />
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}
