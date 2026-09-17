import { Button } from '@/components/ui/button';
import { DatePickerButton } from '@/components/ui/date-picker-button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger, NonModalOverlay } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { ProyectoResumen } from '@/types/proyecto';
import { useForm } from '@inertiajs/react';
import { CalendarClock } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

/**
 * Entrada dedicada para correr la fecha de termino de un Proyecto -- pedido
 * de AVA Montajes (reunion 15-09-2026): "Editar proyecto" ya permite tocar
 * fecha_termino, pero obliga a revisar nombre/descripcion/estado en el mismo
 * formulario para un cambio que en la practica es solo "esto se atraso".
 *
 * Endpoint y evento de historial propios (ProyectoService::aplazarEntrega(),
 * TipoEventoProyecto::EntregaAplazada) en vez de reusar "Editar proyecto":
 * el motivo es obligatorio solo para este aplazamiento, no para cualquier
 * edicion, asi que no puede vivir en ActualizarProyectoRequest -- mismo
 * criterio que RetrocederTareaRequest en tareas. La fecha minima elegible es
 * la fecha de termino actual: "aplazar" solo corre la entrega hacia
 * adelante. Corregir una fecha hacia atras por error sigue siendo trabajo de
 * "Editar proyecto".
 */
export function AplazarEntregaDialog({ proyecto }: { proyecto: ProyectoResumen }) {
    const [open, setOpen] = useState(false);
    const { data, setData, patch, processing, errors, reset } = useForm({
        fecha_termino: proyecto.fecha_termino ?? '',
        motivo: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('proyectos.aplazar-entrega', proyecto.id), {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                reset();
            },
        });
    };

    return (
        <>
            <NonModalOverlay open={open} onClose={() => setOpen(false)} />
            <Dialog
                open={open}
                onOpenChange={(siguiente) => {
                    setOpen(siguiente);
                    if (!siguiente) reset();
                }}
                modal={false}
            >
                <DialogTrigger asChild>
                    <Button type="button" variant="ghost" size="sm">
                        <CalendarClock /> Aplazar entrega
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <form onSubmit={submit}>
                        <DialogHeader>
                            <DialogTitle>Aplazar entrega</DialogTitle>
                            <DialogDescription>Elige la nueva fecha de término e indica el motivo del aplazamiento.</DialogDescription>
                        </DialogHeader>

                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label>Nueva fecha de término</Label>
                                <DatePickerButton
                                    label="Elegir fecha"
                                    valor={data.fecha_termino}
                                    onChange={(valor) => setData('fecha_termino', valor)}
                                    minFecha={proyecto.fecha_termino ?? undefined}
                                    className="h-10 w-full justify-start text-sm"
                                />
                                {errors.fecha_termino && <p className="text-sm text-rojo-1">{errors.fecha_termino}</p>}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="aplazar-motivo">Motivo</Label>
                                <Textarea
                                    id="aplazar-motivo"
                                    placeholder="Por qué se aplaza la entrega"
                                    value={data.motivo}
                                    onChange={(e) => setData('motivo', e.target.value)}
                                    required
                                />
                                {errors.motivo && <p className="text-sm text-rojo-1">{errors.motivo}</p>}
                            </div>
                        </div>

                        <DialogFooter>
                            <Button
                                type="submit"
                                disabled={
                                    processing ||
                                    !data.fecha_termino ||
                                    data.fecha_termino === proyecto.fecha_termino ||
                                    data.motivo.trim() === ''
                                }
                            >
                                Aplazar entrega
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
