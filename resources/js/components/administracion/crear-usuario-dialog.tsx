import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatearRut } from '@/lib/rut';
import { CARGOS_AVA, NIVEL_JERARQUICO_LABELS, type CargoAva, type NivelJerarquico, type UnidadOrganizacional } from '@/types/usuario';
import { useForm } from '@inertiajs/react';
import { UserPlus } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

/** RNF-08: alta minima de usuario (nombre, credenciales, cargo, nivel y unidad). */
export function CrearUsuarioDialog({ unidades }: { unidades: UnidadOrganizacional[] }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        nombre_1: '',
        nombre_2: '',
        apellido_1: '',
        apellido_2: '',
        cargo: '' as CargoAva | '',
        rut: '',
        email: '',
        password: '',
        nivel_jerarquico: '' as NivelJerarquico | '',
        unidad_organizacional_id: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('usuarios.store'), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>
                    <UserPlus /> Nuevo usuario
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-lg">
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>Nuevo usuario</DialogTitle>
                        <DialogDescription>Alta minima de un usuario de prueba: nombre, credenciales, nivel y unidad.</DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="nombre_1">Primer nombre</Label>
                                <Input id="nombre_1" value={data.nombre_1} onChange={(e) => setData('nombre_1', e.target.value)} required />
                                {errors.nombre_1 && <p className="text-sm text-rojo-1">{errors.nombre_1}</p>}
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="nombre_2">Segundo nombre</Label>
                                <Input id="nombre_2" value={data.nombre_2} onChange={(e) => setData('nombre_2', e.target.value)} required />
                                {errors.nombre_2 && <p className="text-sm text-rojo-1">{errors.nombre_2}</p>}
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="apellido_1">Primer apellido</Label>
                                <Input id="apellido_1" value={data.apellido_1} onChange={(e) => setData('apellido_1', e.target.value)} required />
                                {errors.apellido_1 && <p className="text-sm text-rojo-1">{errors.apellido_1}</p>}
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="apellido_2">Segundo apellido</Label>
                                <Input id="apellido_2" value={data.apellido_2} onChange={(e) => setData('apellido_2', e.target.value)} required />
                                {errors.apellido_2 && <p className="text-sm text-rojo-1">{errors.apellido_2}</p>}
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="rut">RUT</Label>
                                <Input
                                    id="rut"
                                    value={data.rut}
                                    onChange={(e) => setData('rut', formatearRut(e.target.value))}
                                    placeholder="12345678-9"
                                    maxLength={10}
                                    required
                                />
                                {errors.rut && <p className="text-sm text-rojo-1">{errors.rut}</p>}
                            </div>
                            <div className="grid gap-2">
                                <Label>Cargo</Label>
                                <Select value={data.cargo} onValueChange={(value) => setData('cargo', value as CargoAva)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Elegir cargo..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {CARGOS_AVA.map((cargo) => (
                                            <SelectItem key={cargo} value={cargo}>
                                                {cargo}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.cargo && <p className="text-sm text-rojo-1">{errors.cargo}</p>}
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Correo</Label>
                            <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required />
                            {errors.email && <p className="text-sm text-rojo-1">{errors.email}</p>}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">Contraseña</Label>
                            <Input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                required
                            />
                            {errors.password && <p className="text-sm text-rojo-1">{errors.password}</p>}
                        </div>

                        <div className="grid grid-cols-2 gap-4">
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
                                <Select
                                    value={data.unidad_organizacional_id}
                                    onValueChange={(value) => setData('unidad_organizacional_id', value)}
                                >
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
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            <UserPlus /> Crear usuario
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
