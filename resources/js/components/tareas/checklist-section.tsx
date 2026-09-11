import { PersonaAvatar } from '@/components/tareas/persona-avatar';
import { PersonaPicker, type Persona } from '@/components/tareas/persona-picker';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import type { SharedData } from '@/types';
import type { ChecklistItem } from '@/types/tarea';
import { router, useForm, usePage } from '@inertiajs/react';
import { Plus, Trash2, UserPlus, X } from 'lucide-react';
import { FormEventHandler } from 'react';

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
    puedeUsar: boolean;
    puedeAsignarDueno: boolean;
}) {
    const { auth } = usePage<SharedData>().props;
    const { data, setData, post, processing, reset } = useForm<{ texto: string; dueno_id: number | null }>({
        texto: '',
        dueno_id: null,
    });

    const duenoSeleccionado = personasElegibles.find((p) => p.id === data.dueno_id) ?? null;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('checklist.store', tareaId), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const alternar = (item: ChecklistItem) => {
        const routeName = item.completado ? 'checklist.desmarcar' : 'checklist.marcar';
        router.patch(route(routeName, item.id), {}, { preserveScroll: true });
    };

    const eliminar = (item: ChecklistItem) => {
        router.delete(route('checklist.destroy', item.id), { preserveScroll: true });
    };

    return (
        <div className="space-y-3">
            {puedeUsar && (
                <form onSubmit={submit} className="space-y-2">
                    <div className="flex gap-2">
                        <Input
                            value={data.texto}
                            onChange={(e) => setData('texto', e.target.value)}
                            placeholder="Agregar un paso..."
                            className="flex-1"
                        />
                        <Button type="submit" size="sm" disabled={processing || data.texto.trim() === ''}>
                            <Plus className="size-4" />
                        </Button>
                    </div>

                    {puedeAsignarDueno &&
                        (duenoSeleccionado ? (
                            <span className="inline-flex items-center gap-1 rounded-full bg-verde-2 py-1 pl-2.5 pr-1 text-xs font-medium text-gris-2">
                                Dueño: {duenoSeleccionado.name}
                                <button
                                    type="button"
                                    onClick={() => setData('dueno_id', null)}
                                    className="rounded-full hover:bg-verde-3"
                                >
                                    <X className="size-3" />
                                </button>
                            </span>
                        ) : (
                            <PersonaPicker
                                personas={personasElegibles}
                                seleccionadosIds={[]}
                                onSelect={(persona) => setData('dueno_id', persona.id)}
                                cerrarAlSeleccionar
                                trigger={
                                    <Button type="button" variant="outline" size="sm" className="w-fit">
                                        <UserPlus /> Asignar dueño
                                    </Button>
                                }
                            />
                        ))}
                </form>
            )}

            {items.length === 0 ? (
                <p className="text-sm text-muted-foreground">Sin ítems todavía.</p>
            ) : (
                <ul className="space-y-1.5">
                    {items.map((item) => {
                        const puedeMarcar = item.dueno_id !== null ? item.dueno_id === auth.user.id : puedeUsar;

                        return (
                            <li key={item.id} className="group flex items-center gap-2">
                                <Checkbox
                                    checked={item.completado}
                                    disabled={!puedeMarcar}
                                    onCheckedChange={() => alternar(item)}
                                    title={!puedeMarcar ? 'Solo el dueño de este ítem puede marcarlo' : undefined}
                                />
                                <span className={cn('flex-1 text-sm text-foreground', item.completado && 'text-muted-foreground line-through')}>
                                    {item.texto}
                                </span>
                                {item.dueno && <PersonaAvatar nombre={item.dueno.name} className="size-6" />}
                                {puedeUsar && (
                                    <button
                                        type="button"
                                        onClick={() => eliminar(item)}
                                        className="text-gris-1 opacity-0 transition-opacity hover:text-rojo-1 group-hover:opacity-100"
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
