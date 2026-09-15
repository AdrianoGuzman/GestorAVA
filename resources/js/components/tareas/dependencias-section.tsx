import { CrearTareaDialog } from '@/components/tareas/crear-tarea-dialog';
import { AtrasadaBadge, EstadoBadge } from '@/components/tareas/estado-badge';
import { PersonaAvatar } from '@/components/tareas/persona-avatar';
import type { Persona } from '@/components/tareas/persona-picker';
import { TareaModalContext } from '@/components/tareas/tarea-modal-context';
import { Button } from '@/components/ui/button';
import { stringAFecha } from '@/components/ui/date-picker-button';
import type { TareaHija } from '@/types/tarea';
import { Link } from '@inertiajs/react';
import { CalendarDays, Plus } from 'lucide-react';
import { useContext } from 'react';

function formatearFecha(fecha: string): string {
    return stringAFecha(fecha.slice(0, 10)).toLocaleDateString('es-CL');
}

/**
 * RF-21/22: tareas hijas de esta tarea. El responsable de una hija se
 * muestra bien visible (avatar + link a su propio detalle) pero no se agrega
 * como colaborador de esta tarea -- son conceptualmente distintos (decision
 * registrada en ContextoProgramacion.md). A diferencia de Checklist, esta
 * seccion nunca se oculta por falta de colaboradores.
 */
export function DependenciasSection({
    tareaId,
    tareasHijas,
    personas,
    puedeCrear,
}: {
    tareaId: number;
    tareasHijas: TareaHija[];
    personas: Persona[];
    puedeCrear: boolean;
}) {
    // Dentro del modal de "Mis tareas" (contexto presente): entrar a la hija
    // reemplaza el contenido del mismo modal en vez de navegar a la pagina
    // completa. En la pagina completa (contexto ausente) el link navega normal.
    const modal = useContext(TareaModalContext);

    return (
        <div className="space-y-3">
            {tareasHijas.length === 0 ? (
                <p className="text-sm text-muted-foreground">Sin tareas hijas todavía.</p>
            ) : (
                <ul className="space-y-1.5">
                    {tareasHijas.map((hija) => (
                        <li
                            key={hija.id}
                            className="flex items-center gap-2 rounded-md border border-gris-3 px-2.5 py-1.5 transition-colors hover:bg-muted/50"
                        >
                            <PersonaAvatar nombre={hija.responsable.name} email={hija.responsable.email} rol="Responsable" className="size-6" />
                            {modal ? (
                                <button
                                    type="button"
                                    onClick={() => modal.abrirRelacionada(hija.id)}
                                    className="min-w-0 flex-1 truncate text-left text-sm font-medium text-foreground hover:underline"
                                >
                                    {hija.codigo} — {hija.titulo}
                                </button>
                            ) : (
                                <Link
                                    href={route('tareas.show', hija.id)}
                                    className="min-w-0 flex-1 truncate text-sm font-medium text-foreground hover:underline"
                                >
                                    {hija.codigo} — {hija.titulo}
                                </Link>
                            )}
                            <span className="flex shrink-0 items-center gap-1 text-xs text-muted-foreground">
                                <CalendarDays className="size-3.5" />
                                {formatearFecha(hija.fecha_compromiso)}
                            </span>
                            {hija.esta_atrasada && hija.estado !== 'completada' && <AtrasadaBadge />}
                            <EstadoBadge estado={hija.estado} />
                        </li>
                    ))}
                </ul>
            )}

            {puedeCrear && (
                <CrearTareaDialog
                    personas={personas}
                    tareaPadreId={tareaId}
                    trigger={
                        <Button type="button" variant="outline" size="sm" className="w-fit">
                            <Plus /> Crear tarea hija
                        </Button>
                    }
                />
            )}
        </div>
    );
}
