import { PersonaPicker, type Persona } from '@/components/tareas/persona-picker';
import { Button } from '@/components/ui/button';
import { NonModalOverlay } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { useAccionTarea } from '@/hooks/use-accion-tarea';
import { UserPlus, X } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

/**
 * RF-06: agregar uno o mas colaboradores. Selector estilo Trello
 * (PersonaPicker) con selección múltiple, en vez de pedir ids a mano.
 */
export function AgregarColaboradorDialog({ trigger, tareaId, personas }: { trigger: React.ReactNode; tareaId: number; personas: Persona[] }) {
    const [open, setOpen] = useState(false);
    const [seleccionadas, setSeleccionadas] = useState<Persona[]>([]);
    const { enviar, processing, errors } = useAccionTarea();

    const alternar = (persona: Persona) => {
        setSeleccionadas((actual) => (actual.some((p) => p.id === persona.id) ? actual.filter((p) => p.id !== persona.id) : [...actual, persona]));
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        enviar(
            'post',
            route('tareas.colaboradores.store', tareaId),
            { colaboradores: seleccionadas.map((persona) => persona.id) },
            {
                onSuccess: () => {
                    setOpen(false);
                    setSeleccionadas([]);
                },
            },
        );
    };

    return (
        // Panel lateral (Sheet) en vez de modal centrado, para no tapar la
        // info de la tarea que queda detras. modal={false}: este dialogo
        // contiene un PersonaPicker (Popover), y cuando ademas esta anidado
        // dentro del modal grande de detalle de tarea (ver
        // tarea-detalle-modal.tsx), el scroll-lock/focus-trap de por medio
        // deja el Popover visible pero totalmente inerte (ni scroll ni click
        // responden). NonModalOverlay repone el fondo difuminado que Radix
        // deja de pintar en ese modo (Sheet usa el mismo primitivo de Radix
        // Dialog por debajo, mismo bug).
        <>
            <NonModalOverlay open={open} onClose={() => setOpen(false)} />
            <Sheet open={open} onOpenChange={setOpen} modal={false}>
                <SheetTrigger asChild>{trigger}</SheetTrigger>
                <SheetContent className="flex flex-col overflow-y-auto">
                    <form onSubmit={submit} className="flex flex-1 flex-col">
                        <SheetHeader>
                            <SheetTitle>Agregar colaborador</SheetTitle>
                            <SheetDescription>El responsable o un colaborador ya existente puede agregar nuevos colaboradores.</SheetDescription>
                        </SheetHeader>

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
                                cerrarAlSeleccionar
                                trigger={
                                    <Button type="button" variant="outline" size="sm" className="w-fit">
                                        <UserPlus /> Agregar persona
                                    </Button>
                                }
                            />

                            {errors.colaboradores && <p className="text-sm text-rojo-1">{errors.colaboradores}</p>}
                        </div>

                        <SheetFooter>
                            <Button type="submit" disabled={processing || seleccionadas.length === 0}>
                                <UserPlus /> Agregar
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>
        </>
    );
}
