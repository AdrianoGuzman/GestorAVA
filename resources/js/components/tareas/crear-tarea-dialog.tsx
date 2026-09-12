import { PersonaPicker, type Persona } from '@/components/tareas/persona-picker';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { SharedData } from '@/types';
import { useForm, usePage } from '@inertiajs/react';
import { Plus, UserPlus, X } from 'lucide-react';
import { FormEventHandler, useEffect, useState } from 'react';

/** RF-04: crear una tarea nueva. El creador queda como responsable salvo que elija a otra persona. */
export function CrearTareaDialog({ trigger, personas }: { trigger: React.ReactNode; personas: Persona[] }) {
    const { auth } = usePage<SharedData>().props;
    const [open, setOpen] = useState(false);
    const [responsable, setResponsable] = useState<Persona | null>(null);
    const [colaboradores, setColaboradores] = useState<Persona[]>([]);
    const { data, setData, post, processing, errors, reset } = useForm({
        titulo: '',
        descripcion: '',
        fecha_inicio: '',
        fecha_compromiso: '',
        responsable_id: '',
        colaboradores: [] as number[],
    });

    useEffect(() => {
        if (open && !responsable) {
            const yoMismo = personas.find((p) => p.id === auth.user.id) ?? { id: auth.user.id, name: auth.user.name, email: auth.user.email };
            setResponsable(yoMismo);
            setData('responsable_id', String(yoMismo.id));
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open]);

    const alternarColaborador = (persona: Persona) => {
        setColaboradores((actual) => {
            const siguiente = actual.some((p) => p.id === persona.id) ? actual.filter((p) => p.id !== persona.id) : [...actual, persona];
            setData('colaboradores', siguiente.map((p) => p.id));
            return siguiente;
        });
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('tareas.store'), {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                setResponsable(null);
                setColaboradores([]);
                reset();
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>Nueva tarea</DialogTitle>
                        <DialogDescription>Por defecto quedás como responsable, salvo que elijas a otra persona.</DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="titulo">Título</Label>
                            <Input id="titulo" value={data.titulo} onChange={(e) => setData('titulo', e.target.value)} required />
                            {errors.titulo && <p className="text-sm text-rojo-1">{errors.titulo}</p>}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="descripcion">Descripción</Label>
                            <Textarea id="descripcion" value={data.descripcion} onChange={(e) => setData('descripcion', e.target.value)} />
                            {errors.descripcion && <p className="text-sm text-rojo-1">{errors.descripcion}</p>}
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="fecha_inicio">Fecha inicio (opcional)</Label>
                                <Input
                                    id="fecha_inicio"
                                    type="date"
                                    value={data.fecha_inicio}
                                    onChange={(e) => setData('fecha_inicio', e.target.value)}
                                />
                                {errors.fecha_inicio && <p className="text-sm text-rojo-1">{errors.fecha_inicio}</p>}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="fecha_compromiso">Fecha término</Label>
                                <Input
                                    id="fecha_compromiso"
                                    type="date"
                                    value={data.fecha_compromiso}
                                    onChange={(e) => setData('fecha_compromiso', e.target.value)}
                                    min={data.fecha_inicio || undefined}
                                    required
                                />
                                {errors.fecha_compromiso && <p className="text-sm text-rojo-1">{errors.fecha_compromiso}</p>}
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label>Responsable</Label>
                            <PersonaPicker
                                personas={personas}
                                seleccionadosIds={responsable ? [responsable.id] : []}
                                cerrarAlSeleccionar
                                onSelect={(persona) => {
                                    setResponsable(persona);
                                    setData('responsable_id', String(persona.id));
                                }}
                                trigger={
                                    <button
                                        type="button"
                                        className="flex w-full items-center gap-2 rounded-md border border-input bg-background px-3 py-2 text-left text-sm hover:bg-verde-1 hover:text-gris-2"
                                    >
                                        {responsable ? (
                                            <>
                                                <Avatar className="size-6">
                                                    <AvatarFallback className="bg-gris-2 text-[10px] text-white">
                                                        {responsable.name.slice(0, 2).toUpperCase()}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <span>{responsable.name}</span>
                                            </>
                                        ) : (
                                            <span className="text-muted-foreground">Elegir persona...</span>
                                        )}
                                    </button>
                                }
                            />
                            {errors.responsable_id && <p className="text-sm text-rojo-1">{errors.responsable_id}</p>}
                        </div>

                        <div className="grid gap-2">
                            <Label>Colaboradores (opcional)</Label>

                            {colaboradores.length > 0 && (
                                <div className="flex flex-wrap gap-1.5">
                                    {colaboradores.map((persona) => (
                                        <span
                                            key={persona.id}
                                            className="inline-flex items-center gap-1 rounded-full bg-verde-2 py-1 pl-2.5 pr-1 text-xs font-medium text-gris-2"
                                        >
                                            {persona.name}
                                            <button
                                                type="button"
                                                onClick={() => alternarColaborador(persona)}
                                                className="rounded-full hover:bg-verde-3"
                                            >
                                                <X className="size-3" />
                                            </button>
                                        </span>
                                    ))}
                                </div>
                            )}

                            <PersonaPicker
                                personas={personas}
                                seleccionadosIds={colaboradores.map((p) => p.id)}
                                onSelect={alternarColaborador}
                                trigger={
                                    <Button type="button" variant="outline" size="sm" className="w-fit">
                                        <UserPlus /> Agregar persona
                                    </Button>
                                }
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={processing || data.titulo.trim() === '' || data.fecha_compromiso === ''}>
                            <Plus /> Crear tarea
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
