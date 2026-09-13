import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

/**
 * Dialogo generico para las 3 acciones de tarea que piden un motivo de
 * texto obligatorio: retroceder (RF-12), rechazar (RF-13) y cancelar (RF-25).
 *
 * `trigger` es opcional: sin el, el dialogo se abre solo via `open`/
 * `onOpenChange` controlados desde afuera -- lo usa el menu "Mas acciones"
 * del detalle de tarea, que dispara `onSelect` en vez de un `DialogTrigger`
 * propio (abrir un Dialog directo desde un DropdownMenuItem es un anti-
 * patron conocido de Radix: los dos compiten por el mismo click).
 */
export function MotivoDialog({
    trigger,
    title,
    description,
    routeName,
    tareaId,
    submitLabel,
    submitIcon: SubmitIcon,
    variant = 'default',
    open: openControlado,
    onOpenChange,
}: {
    trigger?: React.ReactNode;
    title: string;
    description: string;
    routeName: string;
    tareaId: number;
    submitLabel: string;
    submitIcon?: LucideIcon;
    variant?: 'default' | 'destructive';
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
}) {
    const [openInterno, setOpenInterno] = useState(false);
    const open = openControlado ?? openInterno;
    const setOpen = onOpenChange ?? setOpenInterno;
    const { data, setData, patch, processing, errors, reset } = useForm({ motivo: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route(routeName, tareaId), {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                reset();
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            {trigger && <DialogTrigger asChild>{trigger}</DialogTrigger>}
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>{title}</DialogTitle>
                        <DialogDescription>{description}</DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2 py-4">
                        <Label htmlFor="motivo">Motivo</Label>
                        <Textarea
                            id="motivo"
                            value={data.motivo}
                            onChange={(e) => setData('motivo', e.target.value)}
                            required
                            autoFocus
                        />
                        {errors.motivo && <p className="text-sm text-rojo-1">{errors.motivo}</p>}
                    </div>

                    <DialogFooter>
                        <Button type="submit" variant={variant} disabled={processing}>
                            {SubmitIcon && <SubmitIcon />} {submitLabel}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
