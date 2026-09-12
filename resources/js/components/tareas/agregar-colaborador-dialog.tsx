import { PersonaPicker, type Persona } from '@/components/tareas/persona-picker';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger, NonModalOverlay } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { UserPlus, X } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

/**
 * RF-06: agregar uno o mas colaboradores. Selector estilo Trello
 * (PersonaPicker) con selección múltiple, en vez de pedir ids a mano.
 */
export function AgregarColaboradorDialog({ trigger, tareaId, personas }: { trigger: React.ReactNode; tareaId: number; personas: Persona[] }) {
    const [open, setOpen] = useState(false);
    const [seleccionadas, setSeleccionadas] = useState<Persona[]>([]);
    const { post, transform, processing, errors, reset } = useForm({});

    transform(() => ({
        colaboradores: seleccionadas.map((persona) => persona.id),
    }));

    const alternar = (persona: Persona) => {
        setSeleccionadas((actual) => (actual.some((p) => p.id === persona.id) ? actual.filter((p) => p.id !== persona.id) : [...actual, persona]));
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('tareas.colaboradores.store', tareaId), {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                setSeleccionadas([]);
                reset();
            },
        });
    };

    return (
        // modal={false}: este dialogo contiene un PersonaPicker (Popover), y
        // cuando ademas esta anidado dentro del modal grande de detalle de
        // tarea (ver tarea-detalle-modal.tsx), el scroll-lock/focus-trap de
        // un Dialog modal de por medio deja el Popover visible pero
        // totalmente inerte (ni scroll ni click responden). NonModalOverlay
        // repone el fondo difuminado que Radix deja de pintar en ese modo.
        <>
            <NonModalOverlay open={open} onClose={() => setOpen(false)} />
            <Dialog open={open} onOpenChange={setOpen} modal={false}>
                <DialogTrigger asChild>{trigger}</DialogTrigger>
                <DialogContent>
                    <form onSubmit={submit}>
                        <DialogHeader>
                            <DialogTitle>Agregar colaborador</DialogTitle>
                            <DialogDescription>El responsable o un colaborador ya existente puede agregar nuevos colaboradores.</DialogDescription>
                        </DialogHeader>

                        <div className="grid gap-2 py-4">
                            <Label>Colaboradores a agregar</Label>

                            {seleccionadas.length > 0 && (
                                <div className="flex flex-wrap gap-1.5">
                                    {seleccionadas.map((persona) => (
                                        <span
                                            key={persona.id}
                                            className="inline-flex items-center gap-1 rounded-full bg-verde-2 py-1 pl-2.5 pr-1 text-xs font-medium text-gris-2"
                                        >
                                            {persona.name}
                                            <button type="button" onClick={() => alternar(persona)} className="rounded-full hover:bg-verde-3">
                                                <X className="size-3" />
                                            </button>
                                        </span>
                                    ))}
                                </div>
                            )}

                            <PersonaPicker
                                personas={personas}
                                seleccionadosIds={seleccionadas.map((p) => p.id)}
                                onSelect={alternar}
                                trigger={
                                    <Button type="button" variant="outline" size="sm" className="w-fit">
                                        <UserPlus /> Agregar persona
                                    </Button>
                                }
                            />

                            {(errors as Record<string, string>).colaboradores && (
                                <p className="text-sm text-rojo-1">{(errors as Record<string, string>).colaboradores}</p>
                            )}
                        </div>

                        <DialogFooter>
                            <Button type="submit" disabled={processing || seleccionadas.length === 0}>
                                <UserPlus /> Agregar
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
