import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import type { SharedData } from '@/types';
import type { Notificacion, TipoNotificacion } from '@/types/notificacion';
import { router, usePage } from '@inertiajs/react';
import axios from 'axios';
import { formatDistanceToNow } from 'date-fns';
import { es } from 'date-fns/locale';
import {
    AlertTriangle,
    Ban,
    Bell,
    CalendarClock,
    GitBranch,
    Loader2,
    MessageSquareWarning,
    RotateCcw,
    UserPlus,
    UserX,
} from 'lucide-react';
import { useState } from 'react';

const ICONOS: Record<TipoNotificacion, { icon: typeof Bell; className: string }> = {
    delegacion: { icon: UserPlus, className: 'text-verde-6' },
    retroceso: { icon: RotateCcw, className: 'text-gris-1' },
    problema_reportado: { icon: MessageSquareWarning, className: 'text-naranjo-1' },
    no_participacion_reportada: { icon: UserX, className: 'text-gris-1' },
    cancelacion: { icon: Ban, className: 'text-rojo-1' },
    dependencia_creada: { icon: GitBranch, className: 'text-indigo-1' },
    atraso: { icon: AlertTriangle, className: 'text-rojo-1' },
    proximo_vencimiento: { icon: CalendarClock, className: 'text-amarillo-1' },
};

function tiempoRelativo(fecha: string): string {
    return formatDistanceToNow(new Date(fecha), { addSuffix: true, locale: es });
}

/**
 * RF-15/RF-17: unico lugar de la app donde se ven las notificaciones in-app
 * que ya generaban varios servicios (asignacion, retroceso, atraso, proximo
 * vencimiento, etc.) -- hasta ahora quedaban guardadas pero invisibles.
 * El contador viene compartido en cada pagina (auth.notificacionesNoLeidas,
 * ver HandleInertiaRequests); la lista se pide solo al abrir la campana.
 */
export function CampanaNotificaciones() {
    const { auth } = usePage<SharedData>().props;
    const [abierta, setAbierta] = useState(false);
    const [cargando, setCargando] = useState(false);
    const [notificaciones, setNotificaciones] = useState<Notificacion[] | null>(null);

    const cargar = () => {
        setCargando(true);
        axios
            .get<{ notificaciones: Notificacion[] }>(route('notificaciones.index'))
            .then((response) => setNotificaciones(response.data.notificaciones))
            .finally(() => setCargando(false));
    };

    const refrescarContador = () => router.reload({ only: ['auth'] });

    const marcarLeida = (notificacion: Notificacion) => {
        if (notificacion.leida) return;

        setNotificaciones((actual) => actual?.map((n) => (n.id === notificacion.id ? { ...n, leida: true } : n)) ?? null);
        axios.post(route('notificaciones.leer', notificacion.id)).then(refrescarContador);
    };

    const irATarea = (notificacion: Notificacion) => {
        marcarLeida(notificacion);
        setAbierta(false);
        if (notificacion.tarea) router.visit(`/tareas/${notificacion.tarea_id}`);
    };

    const marcarTodasLeidas = () => {
        setNotificaciones((actual) => actual?.map((n) => ({ ...n, leida: true })) ?? null);
        axios.post(route('notificaciones.leer-todas')).then(refrescarContador);
    };

    const hayNoLeidas = notificaciones?.some((n) => !n.leida) ?? false;

    return (
        <Popover
            open={abierta}
            onOpenChange={(open) => {
                setAbierta(open);
                if (open) cargar();
            }}
        >
            <PopoverTrigger asChild>
                <button
                    type="button"
                    className="relative flex size-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                    title="Notificaciones"
                >
                    <Bell className="size-5" />
                    {auth.notificacionesNoLeidas > 0 && (
                        <span className="absolute top-1 right-1 flex size-4 items-center justify-center rounded-full bg-rojo-1 text-[10px] font-semibold text-white">
                            {auth.notificacionesNoLeidas > 9 ? '9+' : auth.notificacionesNoLeidas}
                        </span>
                    )}
                </button>
            </PopoverTrigger>
            <PopoverContent align="end" className="w-96 p-0">
                <div className="flex items-center justify-between border-b border-border px-4 py-3">
                    <span className="text-sm font-semibold text-foreground">Notificaciones</span>
                    {hayNoLeidas && (
                        <Button variant="link" size="sm" className="h-auto p-0 text-xs" onClick={marcarTodasLeidas}>
                            Marcar todas como leídas
                        </Button>
                    )}
                </div>

                <div className="max-h-96 overflow-y-auto">
                    {cargando && (
                        <div className="flex items-center justify-center gap-2 py-10 text-sm text-muted-foreground">
                            <Loader2 className="size-4 animate-spin" /> Cargando...
                        </div>
                    )}

                    {!cargando && notificaciones?.length === 0 && (
                        <p className="py-10 text-center text-sm text-muted-foreground">No tienes notificaciones.</p>
                    )}

                    {!cargando &&
                        notificaciones?.map((notificacion) => {
                            const { icon: Icon, className } = ICONOS[notificacion.tipo];

                            return (
                                <button
                                    key={notificacion.id}
                                    type="button"
                                    onClick={() => irATarea(notificacion)}
                                    className={`flex w-full items-start gap-3 border-b border-border px-4 py-3 text-left text-sm transition-colors last:border-0 hover:bg-accent ${
                                        notificacion.leida ? '' : 'bg-verde-5/10'
                                    }`}
                                >
                                    <Icon className={`mt-0.5 size-4 shrink-0 ${className}`} />
                                    <span className="flex-1 space-y-0.5">
                                        <span className="block text-foreground">{notificacion.mensaje}</span>
                                        <span className="block text-xs text-muted-foreground">{tiempoRelativo(notificacion.created_at)}</span>
                                    </span>
                                    {!notificacion.leida && <span className="mt-1.5 size-2 shrink-0 rounded-full bg-verde-6" />}
                                </button>
                            );
                        })}
                </div>
            </PopoverContent>
        </Popover>
    );
}
