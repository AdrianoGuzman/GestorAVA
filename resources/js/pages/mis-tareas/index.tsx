import { AtrasadaBadge, EstadoBadge } from '@/components/tareas/estado-badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import type { SeccionesMisTareas, SeccionMisTareas, TareaResumen } from '@/types/tarea';
import { Head, Link } from '@inertiajs/react';
import { ArrowRightLeft, Calendar, FilePlus2, LucideIcon, User, Users } from 'lucide-react';

interface Props {
    secciones: SeccionesMisTareas;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Mis tareas', href: '/mis-tareas' }];

const SECCION_CONFIG: Record<keyof SeccionesMisTareas, { titulo: string; icono: LucideIcon; vacio: string }> = {
    responsable: { titulo: 'Como responsable', icono: User, vacio: 'No tenés tareas asignadas como responsable.' },
    colaborador: { titulo: 'Como colaborador', icono: Users, vacio: 'No sos colaborador de ninguna tarea.' },
    delegadas_por_mi: { titulo: 'Delegadas por mí', icono: ArrowRightLeft, vacio: 'No delegaste ninguna tarea.' },
    creadas_por_mi: { titulo: 'Creadas por mí', icono: FilePlus2, vacio: 'No creaste tareas que no seas vos el responsable.' },
};

function formatearFecha(fecha: string): string {
    return new Date(fecha).toLocaleDateString('es-CL');
}

function TareaRow({ tarea, mostrarResponsable }: { tarea: TareaResumen; mostrarResponsable: boolean }) {
    return (
        <Link
            href={`/tareas/${tarea.id}`}
            className="flex flex-wrap items-center justify-between gap-2 rounded-md border border-border p-3 text-sm transition-colors hover:bg-muted/40"
        >
            <div className="min-w-0">
                <p className="truncate font-medium text-foreground">{tarea.titulo}</p>
                {mostrarResponsable && <p className="text-xs text-muted-foreground">Responsable: {tarea.responsable.name}</p>}
            </div>
            <div className="flex shrink-0 items-center gap-2">
                <span className="flex items-center gap-1 text-xs text-muted-foreground">
                    <Calendar className="size-3.5" /> {formatearFecha(tarea.fecha_compromiso)}
                </span>
                <EstadoBadge estado={tarea.estado} />
                {tarea.esta_atrasada && <AtrasadaBadge />}
            </div>
        </Link>
    );
}

function SeccionCard({ clave, seccion }: { clave: keyof SeccionesMisTareas; seccion: SeccionMisTareas }) {
    const config = SECCION_CONFIG[clave];
    const Icono = config.icono;

    return (
        <Card>
            <CardHeader>
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <CardTitle className="flex items-center gap-2 text-base">
                        <Icono className="size-4 text-verde-6" /> {config.titulo}
                    </CardTitle>
                    <div className="flex flex-wrap gap-1.5 text-xs text-muted-foreground">
                        <span>{seccion.contadores.total} en total</span>
                        {seccion.contadores.atrasadas > 0 && <span className="text-rojo-1">{seccion.contadores.atrasadas} atrasadas</span>}
                    </div>
                </div>
            </CardHeader>
            <CardContent className="space-y-2">
                {seccion.tareas.length === 0 ? (
                    <p className="text-sm text-muted-foreground">{config.vacio}</p>
                ) : (
                    seccion.tareas.map((tarea) => <TareaRow key={tarea.id} tarea={tarea} mostrarResponsable={clave !== 'responsable'} />)
                )}
            </CardContent>
        </Card>
    );
}

export default function MisTareasIndex({ secciones }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Mis tareas" />

            <div className="flex flex-col gap-6 p-4">
                {(Object.keys(SECCION_CONFIG) as (keyof SeccionesMisTareas)[])
                    .filter((clave) => secciones[clave] !== undefined)
                    .map((clave) => (
                        <SeccionCard key={clave} clave={clave} seccion={secciones[clave]!} />
                    ))}
            </div>
        </AppLayout>
    );
}
