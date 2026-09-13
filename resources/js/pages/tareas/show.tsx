import { TareaDetalleContent, type TareaDetalleContentProps } from '@/components/tareas/tarea-detalle-content';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

export default function TareaShow(props: TareaDetalleContentProps) {
    const { tarea } = props;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: tarea.titulo, href: `/tareas/${tarea.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={tarea.titulo} />
            <div className="mx-auto max-w-3xl p-4">
                <TareaDetalleContent {...props} />
            </div>
        </AppLayout>
    );
}
