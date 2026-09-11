import { CrearUsuarioDialog } from '@/components/administracion/crear-usuario-dialog';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { NIVEL_JERARQUICO_LABELS, type UnidadOrganizacional, type UsuarioAdmin } from '@/types/usuario';
import { Head } from '@inertiajs/react';

interface Props {
    usuarios: UsuarioAdmin[];
    unidades: UnidadOrganizacional[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Administración', href: '/administracion/usuarios' }];

export default function UsuariosIndex({ usuarios, unidades }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Administración de usuarios" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>Usuarios</CardTitle>
                        <CrearUsuarioDialog unidades={unidades} />
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="text-gris-1">
                                    <tr className="border-b">
                                        <th className="py-2 pr-4 font-medium">Nombre</th>
                                        <th className="py-2 pr-4 font-medium">Correo</th>
                                        <th className="py-2 pr-4 font-medium">Cargo</th>
                                        <th className="py-2 pr-4 font-medium">Nivel jerárquico</th>
                                        <th className="py-2 pr-4 font-medium">Unidad organizacional</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {usuarios.map((usuario) => (
                                        <tr key={usuario.id} className="border-b last:border-0">
                                            <td className="py-2 pr-4">{usuario.name}</td>
                                            <td className="py-2 pr-4">{usuario.email}</td>
                                            <td className="py-2 pr-4">{usuario.cargo}</td>
                                            <td className="py-2 pr-4">
                                                {usuario.nivel_jerarquico ? NIVEL_JERARQUICO_LABELS[usuario.nivel_jerarquico] : '—'}
                                            </td>
                                            <td className="py-2 pr-4">{usuario.unidad_organizacional?.nombre ?? '—'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
