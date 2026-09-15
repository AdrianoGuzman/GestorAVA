import { Button } from '@/components/ui/button';
import { DatePickerButton } from '@/components/ui/date-picker-button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger, NonModalOverlay } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { EstadoProyecto, ProyectoResumen } from '@/types/proyecto';
import { useForm } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

/**
 * Edicion de un Proyecto existente: nombre/descripcion/fechas y su estado
 * (activo/cerrado -- "cerrarlo" es esto, no un borrado, para no perder la
 * trazabilidad de las tareas que agrupó).
 */
export function EditarProyectoDialog({ proyecto }: { proyecto: ProyectoResumen }) {
    const [open, setOpen] = useState(false);
    const { data, setData, patch, processing, errors } = useForm({
        nombre: proyecto.nombre,
        descripcion: proyecto.descripcion ?? '',
        estado: proyecto.estado,
        fecha_inicio: proyecto.fecha_inicio ?? '',
        fecha_termino: proyecto.fecha_termino ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('proyectos.update', proyecto.id), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    return (
        <>
            <NonModalOverlay open={open} onClose={() => setOpen(false)} />
            <Dialog open={open} onOpenChange={setOpen} modal={false}>
                <DialogTrigger asChild>
                    <button
                        type="button"
                        className="rounded-full p-1 -m-1 text-verde-6 transition-colors hover:bg-verde-1 hover:text-verde-6 active:bg-verde-2"
                        title="Editar proyecto"
                    >
                        <Pencil className="size-4" />
                    </button>
                </DialogTrigger>
                <DialogContent>
                    <form onSubmit={submit}>
                        <DialogHeader>
                            <DialogTitle>Editar proyecto</DialogTitle>
                            <DialogDescription>Cerrar un proyecto no borra ni desvincula sus tareas, solo lo marca como finalizado.</DialogDescription>
                        </DialogHeader>

                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="edit-proyecto-nombre">Nombre</Label>
                                <Input id="edit-proyecto-nombre" value={data.nombre} onChange={(e) => setData('nombre', e.target.value)} required />
                                {errors.nombre && <p className="text-sm text-rojo-1">{errors.nombre}</p>}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="edit-proyecto-descripcion">Descripción (opcional)</Label>
                                <Textarea
                                    id="edit-proyecto-descripcion"
                                    value={data.descripcion}
                                    onChange={(e) => setData('descripcion', e.target.value)}
                                />
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

                            <div className="grid gap-2">
                                <Label>Estado</Label>
                                <Select value={data.estado} onValueChange={(valor) => setData('estado', valor as EstadoProyecto)}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="activo">Activo</SelectItem>
                                        <SelectItem value="cerrado">Cerrado</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.estado && <p className="text-sm text-rojo-1">{errors.estado}</p>}
                            </div>
                        </div>

                        <DialogFooter>
                            <Button type="submit" disabled={processing || data.nombre.trim() === '' || !data.fecha_inicio || !data.fecha_termino}>
                                Guardar cambios
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
