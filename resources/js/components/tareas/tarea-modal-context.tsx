import { createContext } from 'react';

/**
 * Presente solo cuando TareaDetalleContent se renderiza dentro del modal de
 * "Mis tareas" (ver tarea-detalle-modal.tsx). Ahi la pagina Inertia "actual"
 * sigue siendo Mis Tareas (el modal nunca navega de verdad) -- las acciones
 * normales de Inertia (router/useForm) redirigen de vuelta ahi, recargando
 * esa pagina completa e invisible (detras del modal) en el medio de cada
 * click. `refrescar` vuelve a pedir el detalle con la misma peticion axios
 * que ya usa el modal para abrirlo, sin ese salto (ver useAccionTarea).
 *
 * `abrirRelacionada`/`volver`/`puedeVolver`: permiten que un link a otra
 * tarea (tarea hija, dependencia) dentro del modal reemplace su contenido en
 * vez de navegar a la pagina completa -- ver DependenciasSection. Se eligio
 * "cambiar el contenido del mismo modal" en vez de abrir un segundo Dialog
 * apilado porque un Dialog de Radix anidado deja inerte cualquier
 * Popover/Sheet interno (el mismo problema que ya obligo a modal={false} +
 * NonModalOverlay en este modal).
 *
 * `volverAlOrigen`: presente solo cuando este modal se abrio ENCIMA de otro
 * (ej. una tarea abierta desde el detalle de un Proyecto, ver
 * proyectos/index.tsx) -- distinto de volver/puedeVolver, que navega entre
 * tareas dentro de este mismo modal. Franco (14-09-2026): sin esto, cerrar
 * el modal de tarea si revela el de proyecto de nuevo (nunca se cerro), pero
 * no habia ningun boton visible que lo dijera.
 *
 * Ausente (null) cuando TareaDetalleContent se renderiza en la pagina
 * completa (`pages/tareas/show.tsx`): ahi back() ya redirige de vuelta a la
 * misma pagina de forma eficiente, sin necesidad de este mecanismo, y un
 * link a otra tarea navega de verdad (no hay modal del que salir).
 */
export const TareaModalContext = createContext<{
    refrescar: () => Promise<unknown>;
    abrirRelacionada: (id: number) => void;
    volver: () => void;
    puedeVolver: boolean;
    volverAlOrigen?: { etiqueta: string; onClick: () => void } | null;
} | null>(null);
