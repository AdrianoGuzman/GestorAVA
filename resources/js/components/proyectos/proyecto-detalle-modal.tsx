import { ProyectoDetalleContent, type ProyectoDetalleContentProps } from '@/components/proyectos/proyecto-detalle-content';
import { Dialog, DialogContent, DialogTitle, NonModalOverlay } from '@/components/ui/dialog';
import { router, usePage } from '@inertiajs/react';
import axios from 'axios';
import { Loader2 } from 'lucide-react';
import { useEffect, useState } from 'react';

/**
 * Mismo patron que useTareaDetalleModal (tarea-detalle-modal.tsx): pide
 * `/proyectos/{id}` con los headers de Inertia para traer solo los props en
 * JSON, y los renderiza en un modal sin navegar fuera de "Mis proyectos"
 * (Franco, 14-09-2026: "deberían abrirse en modal y no ventana aparte").
 */
export function useProyectoDetalleModal() {
    const { version } = usePage();
    const [proyectoId, setProyectoId] = useState<number | null>(null);
    const [datos, setDatos] = useState<Omit<ProyectoDetalleContentProps, 'onAbrirTarea'> | null>(null);
    const [cargando, setCargando] = useState(false);

    const cargar = (id: number, mostrarSpinner: boolean) => {
        if (mostrarSpinner) setCargando(true);

        return axios
            .get(`/proyectos/${id}`, {
                headers: {
                    'X-Inertia': true,
                    'X-Inertia-Version': version ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'text/html, application/xhtml+xml',
                },
            })
            .then((response) => setDatos(response.data.props))
            .catch(() => {
                if (mostrarSpinner) router.visit(`/proyectos/${id}`);
            })
            .finally(() => {
                if (mostrarSpinner) setCargando(false);
            });
    };

    const abrir = (id: number) => {
        setProyectoId(id);
        setDatos(null);
        cargar(id, true);
    };

    const cerrar = () => {
        setProyectoId(null);
        setDatos(null);
    };

    // Red de seguridad: crear/editar seccion, editar proyecto y crear tarea
    // (todas via router/useForm normal, no un contexto propio como
    // useAccionTarea) redirigen de vuelta a "Mis proyectos" -- refrescamos
    // los datos del proyecto abierto cada vez que una de esas acciones
    // termina bien, sin mostrar el spinner grande.
    useEffect(() => {
        if (proyectoId === null) return;

        return router.on('success', () => cargar(proyectoId, false));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [proyectoId]);

    return { proyectoId, datos, cargando, abrir, cerrar };
}

export function ProyectoDetalleModal({
    proyectoId,
    datos,
    cargando,
    onClose,
    onAbrirTarea,
}: {
    proyectoId: number | null;
    datos: Omit<ProyectoDetalleContentProps, 'onAbrirTarea'> | null;
    cargando: boolean;
    onClose: () => void;
    onAbrirTarea: (id: number) => void;
}) {
    return (
        <>
            {/* z-30 (menos que el z-40 por defecto): este modal puede quedar
                abierto "por debajo" mientras se abre una tarea desde adentro (ver
                TareaDetalleModal, siempre z-40/z-50) -- con el mismo z-index en
                los dos, cual queda arriba dependia del orden de montaje en el DOM
                (ambiguo entre dos Dialog hermanos declarados desde el arranque de
                la pagina, a diferencia de un dialogo hijo que siempre monta
                despues de su padre). Franco (14-09-2026): "no corregiste bien eso". */}
            <NonModalOverlay open={proyectoId !== null} onClose={onClose} className="z-30" />

            {/* modal={false}: adentro se abren CrearSeccionDialog/CrearTareaDialog
                (con Popovers de fecha/persona) y, si se hace clic en una tarea, el
                TareaDetalleModal tambien anidado -- mismo motivo que
                tarea-detalle-modal.tsx: con el modal de aca atrapando el foco, esos
                anidados quedan visibles pero inertes. */}
            <Dialog open={proyectoId !== null} onOpenChange={(open) => !open && onClose()} modal={false}>
                <DialogContent className="z-[45] max-h-[92vh] max-w-3xl overflow-y-auto">
                    <DialogTitle className="sr-only">{datos?.proyecto.nombre ?? 'Detalle de proyecto'}</DialogTitle>
                    {cargando || !datos ? (
                        <div className="flex items-center justify-center gap-2 py-16 text-muted-foreground">
                            <Loader2 className="size-5 animate-spin" /> Cargando proyecto...
                        </div>
                    ) : (
                        <ProyectoDetalleContent proyecto={datos.proyecto} usuarios={datos.usuarios} onAbrirTarea={onAbrirTarea} />
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}
