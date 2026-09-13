import { PersonaPicker, type Persona } from '@/components/tareas/persona-picker';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { NonModalOverlay } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Textarea } from '@/components/ui/textarea';
import { useAccionTarea } from '@/hooks/use-accion-tarea';
import { useForm } from '@inertiajs/react';
import { UserCog } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

/**
 * RF-05: reasignar responsable, incluyendo el camino de excepcion RN-12.
 * Selector de persona estilo Trello (PersonaPicker) en vez de pedir un id.
 */
export function ReasignarDialog({ trigger, tareaId, personas }: { trigger: React.ReactNode; tareaId: number; personas: Persona[] }) {
    const [open, setOpen] = useState(false);
    const [nuevoResponsable, setNuevoResponsable] = useState<Persona | null>(null);
    const { data, setData, reset } = useForm({
        nuevo_responsable_id: '',
        mantener_como_colaborador: false as boolean,
        es_excepcion: false as boolean,
        motivo_excepcion: '',
    });
    const { enviar, processing, errors } = useAccionTarea();

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        enviar('patch', route('tareas.reasignar', tareaId), data, {
            onSuccess: () => {
                setOpen(false);
                setNuevoResponsable(null);
                reset();
            },
        });
    };

    return (
        // Panel lateral (Sheet) en vez de modal centrado, para no tapar la
        // info de la tarea que queda detras. modal={false}: este dialogo
        // tambien contiene un PersonaPicker (Popover) y puede quedar anidado
        // dentro del modal grande de detalle de tarea -- mismo motivo que en
        // AgregarColaboradorDialog. NonModalOverlay repone el fondo
        // difuminado que Radix deja de pintar en ese modo (Sheet usa el
        // mismo primitivo de Radix Dialog por debajo, mismo bug).
        <>
            <NonModalOverlay open={open} onClose={() => setOpen(false)} />
            <Sheet open={open} onOpenChange={setOpen} modal={false}>
                <SheetTrigger asChild>{trigger}</SheetTrigger>
                <SheetContent className="flex flex-col overflow-y-auto">
                    <form onSubmit={submit} className="flex flex-1 flex-col">
                        <SheetHeader>
                            <SheetTitle>Reasignar responsable</SheetTitle>
                            <SheetDescription>
                                El responsable actual, o su superior jerárquico directo de la misma unidad, puede reasignar esta tarea.
                            </SheetDescription>
                        </SheetHeader>

                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label>Nuevo responsable</Label>
                                <PersonaPicker
                                    personas={personas}
                                    seleccionadosIds={nuevoResponsable ? [nuevoResponsable.id] : []}
                                    cerrarAlSeleccionar
                                    onSelect={(persona) => {
                                        setNuevoResponsable(persona);
                                        setData('nuevo_responsable_id', String(persona.id));
                                    }}
                                    trigger={
                                        <button
                                            type="button"
                                            className="flex w-full items-center gap-2 rounded-md border border-input bg-background px-3 py-2 text-left text-sm hover:bg-verde-1 hover:text-gris-2"
                                        >
                                            {nuevoResponsable ? (
                                                <>
                                                    <Avatar className="size-6">
                                                        <AvatarFallback className="bg-gris-2 text-[10px] text-white">
                                                            {nuevoResponsable.name.slice(0, 2).toUpperCase()}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <span>{nuevoResponsable.name}</span>
                                                </>
                                            ) : (
                                                <span className="text-muted-foreground">Elegir persona...</span>
                                            )}
                                        </button>
                                    }
                                />
                                {errors.nuevo_responsable_id && <p className="text-sm text-rojo-1">{errors.nuevo_responsable_id}</p>}
                            </div>

                            <label className="flex items-center gap-2 text-sm">
                                <Checkbox
                                    checked={data.mantener_como_colaborador}
                                    onCheckedChange={(checked) => setData('mantener_como_colaborador', checked === true)}
                                />
                                El responsable saliente queda como colaborador
                            </label>

                            <label className="flex items-center gap-2 text-sm">
                                <Checkbox checked={data.es_excepcion} onCheckedChange={(checked) => setData('es_excepcion', checked === true)} />
                                Excepción por ausencia total (RN-12)
                            </label>

                            {data.es_excepcion && (
                                <div className="grid gap-2">
                                    <Label htmlFor="motivo_excepcion">Motivo de la excepción</Label>
                                    <Textarea
                                        id="motivo_excepcion"
                                        value={data.motivo_excepcion}
                                        onChange={(e) => setData('motivo_excepcion', e.target.value)}
                                        required
                                    />
                                    {errors.motivo_excepcion && <p className="text-sm text-rojo-1">{errors.motivo_excepcion}</p>}
                                </div>
                            )}
                        </div>

                        <SheetFooter>
                            <Button type="submit" disabled={processing || !nuevoResponsable}>
                                <UserCog /> Reasignar
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>
        </>
    );
}
