import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { NIVEL_JERARQUICO_LABELS, type NivelJerarquico, type UnidadOrganizacional, type UsuarioAdmin } from '@/types/usuario';
import { useForm } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

/**
 * RNF-08: edicion minima de un usuario existente -- solo nivel jerarquico
 * y unidad organizacional (el resto de sus datos no se edita aqui, eso es
 * la administracion completa de Fase 2).
 */
export function EditarUsuarioDialog({ usuario, unidades }: { usuario: UsuarioAdmin; unidades: UnidadOrganizacional[] }) {
    const [open, setOpen] = useState(false);
    const { data, setData, patch, processing, errors } = useForm({
        nivel_jerarquico: (usuario.nivel_jerarquico ?? '') as NivelJerarquico | '',
        unidad_organizacional_id: usuario.unidad_organizacional_id ? String(usuario.unidad_organizacional_id) : '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('usuarios.update', usuario.id), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    <Pencil /> Editar
                </Button>
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>Editar {usuario.name}</DialogTitle>
                        <DialogDescription>Cambiar nivel jerárquico y unidad organizacional.</DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label>Nivel jerárquico</Label>
                            <Select value={data.nivel_jerarquico} onValueChange={(value) => setData('nivel_jerarquico', value as NivelJerarquico)}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Elegir nivel..." />
                                </SelectTrigger>
                                <SelectContent>
                                    {(Object.keys(NIVEL_JERARQUICO_LABELS) as NivelJerarquico[]).map((nivel) => (
                                        <SelectItem key={nivel} value={nivel}>
                                            {NIVEL_JERARQUICO_LABELS[nivel]}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.nivel_jerarquico && <p className="text-sm text-rojo-1">{errors.nivel_jerarquico}</p>}
                        </div>

                        <div className="grid gap-2">
                            <Label>Unidad organizacional</Label>
                            <Select value={data.unidad_organizacional_id} onValueChange={(value) => setData('unidad_organizacional_id', value)}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Elegir unidad..." />
                                </SelectTrigger>
                                <SelectContent>
                                    {unidades.map((unidad) => (
                                        <SelectItem key={unidad.id} value={String(unidad.id)}>
                                            {unidad.nombre}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.unidad_organizacional_id && <p className="text-sm text-rojo-1">{errors.unidad_organizacional_id}</p>}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Guardar cambios
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
