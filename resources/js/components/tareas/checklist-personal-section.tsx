import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { useAccionTarea } from '@/hooks/use-accion-tarea';
import { cn } from '@/lib/utils';
import type { ChecklistPersonalItem } from '@/types/tarea';
import { useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

/**
 * "Mi checklist": guia personal y privada, sin dueño que asignar y sin
 * bloquear nada. Distinta del checklist compartido de RF-23.
 */
export function ChecklistPersonalSection({ tareaId, items }: { tareaId: number; items: ChecklistPersonalItem[] }) {
    const { data, setData, reset } = useForm({ texto: '' });
    const agregar = useAccionTarea();
    const accionMarcar = useAccionTarea();
    const accionEliminar = useAccionTarea();

    /** Ver nota en ChecklistSection sobre por que esto es optimista. */
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

        agregar.enviar('post', route('tareas.checklist-personal.store', tareaId), data, { onSuccess: () => reset() });
    };

    const alternar = (item: ChecklistPersonalItem) => {
        setCompletadoOptimista((prev) => ({ ...prev, [item.id]: !item.completado }));

        accionMarcar.enviar(
            'patch',
            route('tareas.checklist-personal.alternar', [tareaId, item.id]),
            {},
            {
                onSuccess: () => quitarOptimista(item.id),
                onError: () => quitarOptimista(item.id),
            },
        );
    };

    const eliminar = (item: ChecklistPersonalItem) => {
        setEliminadosOptimista((prev) => new Set(prev).add(item.id));

        accionEliminar.enviar(
            'delete',
            route('tareas.checklist-personal.destroy', [tareaId, item.id]),
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
            <form
                onSubmit={submit}
                className="flex items-center rounded-md border border-input bg-background pr-1 transition-colors focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2"
            >
                <Input
                    value={data.texto}
                    onChange={(e) => setData('texto', e.target.value)}
                    placeholder="Agregar un paso..."
                    className="h-9 flex-1 border-0 bg-transparent shadow-none focus-visible:ring-0 focus-visible:ring-offset-0"
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

            {itemsVisibles.length === 0 ? (
                <p className="text-sm text-muted-foreground">Es tu guía personal — no afecta la tarea ni la ve nadie más.</p>
            ) : (
                <ul className="space-y-1.5">
                    {itemsVisibles.map((item) => (
                        <li
                            key={item.id}
                            className="group flex items-center gap-2 rounded-md px-2 py-1.5 transition-colors hover:bg-muted/50"
                        >
                            <Checkbox
                                checked={item.completado}
                                onCheckedChange={() => alternar(item)}
                                className="size-5 rounded-full border-gris-1/40 transition-colors duration-200 data-[state=checked]:border-verde-5 data-[state=checked]:bg-verde-5 data-[state=checked]:text-gris-2"
                            />
                            <span className={cn('flex-1 text-sm text-foreground transition-colors', item.completado && 'text-muted-foreground')}>
                                {item.texto}
                            </span>
                            <button
                                type="button"
                                onClick={() => eliminar(item)}
                                className="rounded-full p-1 text-gris-1 opacity-0 transition-colors group-hover:opacity-100 hover:bg-rojo-1/10 hover:text-rojo-1"
                                title="Eliminar"
                            >
                                <Trash2 className="size-3.5" />
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
