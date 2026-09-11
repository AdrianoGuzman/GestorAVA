import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import type { ChecklistPersonalItem } from '@/types/tarea';
import { router, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { FormEventHandler } from 'react';

/**
 * "Mi checklist": guia personal y privada, sin dueño que asignar y sin
 * bloquear nada. Distinta del checklist compartido de RF-23.
 */
export function ChecklistPersonalSection({ tareaId, items }: { tareaId: number; items: ChecklistPersonalItem[] }) {
    const { data, setData, post, processing, reset } = useForm({ texto: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('tareas.checklist-personal.store', tareaId), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const alternar = (item: ChecklistPersonalItem) => {
        router.patch(route('tareas.checklist-personal.alternar', [tareaId, item.id]), {}, { preserveScroll: true });
    };

    const eliminar = (item: ChecklistPersonalItem) => {
        router.delete(route('tareas.checklist-personal.destroy', [tareaId, item.id]), { preserveScroll: true });
    };

    return (
        <div className="space-y-3">
            <form onSubmit={submit} className="flex gap-2">
                <Input
                    value={data.texto}
                    onChange={(e) => setData('texto', e.target.value)}
                    placeholder="Agregar un paso..."
                    className="flex-1"
                />
                <Button type="submit" size="sm" disabled={processing || data.texto.trim() === ''}>
                    <Plus className="size-4" />
                </Button>
            </form>

            {items.length === 0 ? (
                <p className="text-sm text-muted-foreground">Es tu guía personal — no afecta la tarea ni la ve nadie más.</p>
            ) : (
                <ul className="space-y-1.5">
                    {items.map((item) => (
                        <li key={item.id} className="group flex items-center gap-2">
                            <Checkbox checked={item.completado} onCheckedChange={() => alternar(item)} />
                            <span className={cn('flex-1 text-sm text-foreground', item.completado && 'text-muted-foreground line-through')}>
                                {item.texto}
                            </span>
                            <button
                                type="button"
                                onClick={() => eliminar(item)}
                                className="text-gris-1 opacity-0 transition-opacity hover:text-rojo-1 group-hover:opacity-100"
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
