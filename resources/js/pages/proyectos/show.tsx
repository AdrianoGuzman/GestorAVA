import { ProyectoDetalleContent } from '@/components/proyectos/proyecto-detalle-content';
import type { Persona } from '@/components/tareas/persona-picker';
import { TareaDetalleModal, useTareaDetalleModal } from '@/components/tareas/tarea-detalle-modal';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import type { ProyectoDetalle } from '@/types/proyecto';
import { Head } from '@inertiajs/react';

interface Props {
    proyecto: ProyectoDetalle;
    usuarios: Persona[];
}

/**
 * Pagina completa de respaldo (link directo, compartir, etc.) -- dentro de
 * "Mis proyectos" el flujo normal abre esto mismo en un modal, ver
 * proyecto-detalle-modal.tsx. Mismo patron que pages/tareas/show.tsx.
 */
export default function ProyectoShow({ proyecto, usuarios }: Props) {
    const modal = useTareaDetalleModal();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Mis proyectos', href: '/mis-proyectos' },
        { title: proyecto.nombre, href: `/proyectos/${proyecto.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={proyecto.nombre} />

            <div className="mx-auto flex w-full max-w-3xl flex-1 flex-col p-4">
                <ProyectoDetalleContent proyecto={proyecto} usuarios={usuarios} onAbrirTarea={modal.abrir} />
            </div>

            <TareaDetalleModal
                tareaId={modal.tareaId}
                datos={modal.datos}
                cargando={modal.cargando}
                onClose={modal.cerrar}
                refrescar={modal.refrescar}
                abrirRelacionada={modal.abrirRelacionada}
                volver={modal.volver}
                puedeVolver={modal.puedeVolver}
            />
        </AppLayout>
    );
}
