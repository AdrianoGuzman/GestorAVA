import { PersonaAvatar } from '@/components/tareas/persona-avatar';
import { PersonaPicker, type Persona } from '@/components/tareas/persona-picker';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { circuloOpcionClasses, DatePickerButton } from '@/components/ui/date-picker-button';
import { Input } from '@/components/ui/input';
import { useAccionTarea } from '@/hooks/use-accion-tarea';
import { cn } from '@/lib/utils';
import type { SharedData } from '@/types';
import type { ChecklistItem } from '@/types/tarea';
import { useForm, usePage } from '@inertiajs/react';
import { CalendarDays, Plus, Trash2, UserPlus, X } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

function iniciales(nombre: string): string {
    return (
        nombre
            .split(' ')
            .filter(Boolean)
            .slice(0, 2)
            .map((parte) => parte[0]?.toUpperCase())
            .join('') || '?'
    );
}

function formatearFecha(fecha: string): string {
    return new Date(fecha).toLocaleDateString('es-CL');
}

function estaVencido(item: ChecklistItem): boolean {
    if (item.completado || !item.fecha_limite) return false;
    return new Date(item.fecha_limite) < new Date(new Date().toDateString());
}

/**
 * Checklist compartido (RF-23). Solo se renderiza si la tarea tiene
 * colaboradores (ver show.tsx); si el responsable es el único involucrado,
 * usa "Mi checklist" en su lugar.
 */
export function ChecklistSection({
    tareaId,
    items,
    personasElegibles,
    puedeUsar,
    puedeAsignarDueno,
}: {
    tareaId: number;
    items: ChecklistItem[];
    personasElegibles: Persona[];
    /** false para una tarea completada/cancelada -- ya no admite items nuevos, editarlos, eliminarlos ni marcarlos. */
    puedeUsar: boolean;
    puedeAsignarDueno: boolean;
}) {
    const { auth } = usePage<SharedData>().props;
    const { data, setData, reset } = useForm<{ texto: string; dueno_id: number | null; fecha_limite: string }>({
        texto: '',
        dueno_id: null,
        fecha_limite: '',
    });
    const agregar = useAccionTarea();
    const accionMarcar = useAccionTarea();
    const accionEliminar = useAccionTarea();

    const duenoSeleccionado = personasElegibles.find((p) => p.id === data.dueno_id) ?? null;

    /**
     * El modal de tarea nunca "navega" de verdad (ver tarea-detalle-modal.tsx),
     * asi que cada accion espera un ciclo completo de ida y vuelta -- incluida
     * una recarga invisible de "Mis tareas" -- antes de reflejarse. Eso se
     * siente como un retraso, sobre todo marcando varios items seguidos.
     * Actualizamos el estado local al instante (optimista) y lo revertimos
     * solo si el servidor termina rechazando la accion.
     */
    const [completadoOptimista, setCompletadoOptimista] = useState<Record<number, boolean>>({});
    const [eliminadosOptimista, setEliminadosOptimista] = useState<Set<number>>(new Set());

    const quitarOptimista = (id: number) => {
        setCompletadoOptimista((prev) => {
            if (!(id in prev)) return prev;
            const { [id]: _omitido, ...resto } = prev;
            return resto;
        });
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        agregar.enviar('post', route('checklist.store', tareaId), data, { onSuccess: () => reset() });
    };

    const alternar = (item: ChecklistItem) => {
        const nuevoEstado = !item.completado;
        setCompletadoOptimista((prev) => ({ ...prev, [item.id]: nuevoEstado }));

        const routeName = item.completado ? 'checklist.desmarcar' : 'checklist.marcar';
        accionMarcar.enviar(
            'patch',
            route(routeName, item.id),
            {},
            {
                onSuccess: () => quitarOptimista(item.id),
                onError: () => quitarOptimista(item.id),
            },
        );
    };

    const eliminar = (item: ChecklistItem) => {
        setEliminadosOptimista((prev) => new Set(prev).add(item.id));

        accionEliminar.enviar(
            'delete',
            route('checklist.destroy', item.id),
            {},
            {
                onError: () =>
                    setEliminadosOptimista((prev) => {
                        const siguiente = new Set(prev);
                        siguiente.delete(item.id);
                        return siguiente;
                    }),
            },
        );
    };

    const itemsVisibles = items
        .filter((item) => !eliminadosOptimista.has(item.id))
        .map((item) => (item.id in completadoOptimista ? { ...item, completado: completadoOptimista[item.id] } : item));

    return (
        <div className="space-y-3">
            {puedeUsar && (
                <form
                    onSubmit={submit}
                    className="flex items-center gap-1.5 rounded-md border border-input bg-background py-1 pr-1 pl-2 transition-colors focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2"
                >
                    <Input
                        value={data.texto}
                        onChange={(e) => setData('texto', e.target.value)}
                        placeholder="Agregar un paso..."
                        className="h-8 flex-1 border-0 bg-transparent p-0 shadow-none focus-visible:ring-0 focus-visible:ring-offset-0"
                    />

                    {puedeAsignarDueno && (
                        <div className="relative shrink-0">
                            <PersonaPicker
                                personas={personasElegibles}
                                seleccionadosIds={[]}
                                onSelect={(persona) => setData('dueno_id', persona.id)}
                                cerrarAlSeleccionar
                                trigger={
                                    <button
                                        type="button"
                                        title={duenoSeleccionado ? `Dueño: ${duenoSeleccionado.name}` : 'Asignar dueño'}
                                        className={circuloOpcionClasses(!!duenoSeleccionado)}
                                    >
                                        {duenoSeleccionado ? (
                                            <span className="text-[10px] font-semibold">{iniciales(duenoSeleccionado.name)}</span>
                                        ) : (
                                            <UserPlus className="size-3.5" />
                                        )}
                                    </button>
                                }
                            />
                            {duenoSeleccionado && (
                                <button
                                    type="button"
                                    onClick={() => setData('dueno_id', null)}
                                    title="Quitar dueño"
                                    className="absolute -top-1 -right-1 flex size-3.5 items-center justify-center rounded-full bg-rojo-1 text-white"
                                >
                                    <X className="size-2" />
                                </button>
                            )}
                        </div>
                    )}

                    <DatePickerButton
                        label="Fecha límite"
                        valor={data.fecha_limite}
                        onChange={(valor) => setData('fecha_limite', valor)}
                        soloFuturo
                        compact
                    />

                    <Button
                        type="submit"
                        size="icon"
                        variant="ghost"
                        className="size-7 shrink-0 rounded-full text-verde-6 hover:bg-verde-1 hover:text-verde-6"
                        disabled={agregar.processing || data.texto.trim() === ''}
                    >
                        <Plus className="size-4" />
                    </Button>
                </form>
            )}

            {itemsVisibles.length === 0 ? (
                <p className="text-sm text-muted-foreground">Sin ítems todavía.</p>
            ) : (
                <ul className="space-y-1.5">
                    {itemsVisibles.map((item) => {
                        const puedeMarcar = puedeUsar && (item.dueno_id === null || item.dueno_id === auth.user.id);

                        return (
                            <li
                                key={item.id}
                                className="group flex items-center gap-2 rounded-md px-2 py-1.5 transition-colors hover:bg-muted/50"
                            >
                                <Checkbox
                                    checked={item.completado}
                                    disabled={!puedeMarcar}
                                    onCheckedChange={() => alternar(item)}
                                    title={!puedeMarcar ? (puedeUsar ? 'Solo el dueño de este ítem puede marcarlo' : 'La tarea ya está cerrada') : undefined}
                                    className="size-5 rounded-full border-gris-1/40 transition-colors duration-200 data-[state=checked]:border-verde-5 data-[state=checked]:bg-verde-5 data-[state=checked]:text-gris-2"
                                />
                                <span className={cn('flex-1 text-sm text-foreground transition-colors', item.completado && 'text-muted-foreground')}>
                                    {item.texto}
                                </span>
                                {item.fecha_limite && (
                                    <span
                                        className={cn(
                                            'flex shrink-0 items-center gap-1 text-xs text-muted-foreground',
                                            estaVencido(item) && 'font-medium text-rojo-1',
                                        )}
                                    >
                                        <CalendarDays className="size-3.5" />
                                        {formatearFecha(item.fecha_limite)}
                                    </span>
                                )}
                                {item.dueno && (
                                    <PersonaAvatar nombre={item.dueno.name} email={item.dueno.email} rol="Dueño del ítem" className="size-6" />
                                )}
                                {puedeUsar && (
                                    <button
                                        type="button"
                                        onClick={() => eliminar(item)}
                                        className="rounded-full p-1 text-gris-1 opacity-0 transition-colors group-hover:opacity-100 hover:bg-rojo-1/10 hover:text-rojo-1"
                                        title="Eliminar"
                                    >
                                        <Trash2 className="size-3.5" />
                                    </button>
                                )}
                            </li>
                        );
                    })}
                </ul>
            )}
        </div>
    );
}
