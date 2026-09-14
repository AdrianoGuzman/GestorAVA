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
 * Ausente (null) cuando TareaDetalleContent se renderiza en la pagina
 * completa (`pages/tareas/show.tsx`): ahi back() ya redirige de vuelta a la
 * misma pagina de forma eficiente, sin necesidad de este mecanismo.
 */
export const TareaModalContext = createContext<{ refrescar: () => Promise<unknown> } | null>(null);
