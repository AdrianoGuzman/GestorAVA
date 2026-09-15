import { Button } from '@/components/ui/button';
import { DatePickerButton } from '@/components/ui/date-picker-button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger, NonModalOverlay } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/react';
import { FolderPlus } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

/** Alta de un Proyecto (agrupa tareas de varias unidades bajo una misma iniciativa). Solo Directorio/Gerencia, ver NivelJerarquico::puedeAdministrarProyectos(). */
export function CrearProyectoDialog() {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        nombre: '',
        descripcion: '',
        fecha_inicio: '',
        fecha_termino: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('proyectos.store'), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    return (
        // modal={false}: las fechas usan DatePickerButton (un Popover) -- con el
        // modal atrapando el foco, el Popover queda visible pero inerte. Mismo
        // patron que crear-tarea-dialog.tsx.
        <>
            <NonModalOverlay open={open} onClose={() => setOpen(false)} />
            <Dialog open={open} onOpenChange={setOpen} modal={false}>
                <DialogTrigger asChild>
                    <Button className="bg-verde-5 text-gris-2 hover:bg-verde-6">
                        <FolderPlus /> Nuevo proyecto
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <form onSubmit={submit}>
                        <DialogHeader>
                            <DialogTitle>Nuevo proyecto</DialogTitle>
                            <DialogDescription>Agrupa tareas de una o varias unidades bajo una misma iniciativa (ej. "Cultura preventiva").</DialogDescription>
                        </DialogHeader>

                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="nombre">Nombre</Label>
                                <Input id="nombre" value={data.nombre} onChange={(e) => setData('nombre', e.target.value)} required />
                                {errors.nombre && <p className="text-sm text-rojo-1">{errors.nombre}</p>}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="descripcion">Descripción (opcional)</Label>
                                <Textarea id="descripcion" value={data.descripcion} onChange={(e) => setData('descripcion', e.target.value)} />
                                {errors.descripcion && <p className="text-sm text-rojo-1">{errors.descripcion}</p>}
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label>Fecha inicio</Label>
                                    <DatePickerButton
                                        label="Elegir fecha"
                                        valor={data.fecha_inicio}
                                        onChange={(valor) => setData('fecha_inicio', valor)}
                                        className="h-10 w-full justify-start text-sm"
                                    />
                                    {errors.fecha_inicio && <p className="text-sm text-rojo-1">{errors.fecha_inicio}</p>}
                                </div>

                                <div className="grid gap-2">
                                    <Label>Fecha término</Label>
                                    <DatePickerButton
                                        label="Elegir fecha"
                                        valor={data.fecha_termino}
                                        onChange={(valor) => setData('fecha_termino', valor)}
                                        minFecha={data.fecha_inicio || undefined}
                                        className="h-10 w-full justify-start text-sm"
                                    />
                                    {errors.fecha_termino && <p className="text-sm text-rojo-1">{errors.fecha_termino}</p>}
                                </div>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button type="submit" disabled={processing || data.nombre.trim() === '' || !data.fecha_inicio || !data.fecha_termino}>
                                <FolderPlus /> Crear proyecto
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
