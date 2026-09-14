import type { ToastProps } from '@/components/ui/toast';
import * as React from 'react';

const LIMITE_TOASTS = 3;
const DEMORA_CIERRE_MS = 5000;

type ToasterToast = ToastProps & {
    id: string;
    title?: React.ReactNode;
    description?: React.ReactNode;
};

type Accion =
    | { type: 'AGREGAR'; toast: ToasterToast }
    | { type: 'CERRAR'; toastId?: string }
    | { type: 'QUITAR'; toastId?: string };

interface Estado {
    toasts: ToasterToast[];
}

let contador = 0;
function generarId(): string {
    contador = (contador + 1) % Number.MAX_SAFE_INTEGER;
    return contador.toString();
}

const temporizadores = new Map<string, ReturnType<typeof setTimeout>>();

function encolarQuitar(toastId: string) {
    if (temporizadores.has(toastId)) return;

    const temporizador = setTimeout(() => {
        temporizadores.delete(toastId);
        despachar({ type: 'QUITAR', toastId });
    }, DEMORA_CIERRE_MS);

    temporizadores.set(toastId, temporizador);
}

function reducir(estado: Estado, accion: Accion): Estado {
    switch (accion.type) {
        case 'AGREGAR':
            return { toasts: [accion.toast, ...estado.toasts].slice(0, LIMITE_TOASTS) };
        case 'CERRAR': {
            const { toastId } = accion;
            if (toastId) {
                encolarQuitar(toastId);
            } else {
                estado.toasts.forEach((toast) => encolarQuitar(toast.id));
            }
            return {
                toasts: estado.toasts.map((toast) => (toastId === undefined || toast.id === toastId ? { ...toast, open: false } : toast)),
            };
        }
        case 'QUITAR':
            if (accion.toastId === undefined) return { toasts: [] };
            return { toasts: estado.toasts.filter((toast) => toast.id !== accion.toastId) };
    }
}

const listeners: Array<(estado: Estado) => void> = [];
let estadoEnMemoria: Estado = { toasts: [] };

function despachar(accion: Accion) {
    estadoEnMemoria = reducir(estadoEnMemoria, accion);
    listeners.forEach((listener) => listener(estadoEnMemoria));
}

type Toast = Omit<ToasterToast, 'id'>;

/**
 * Se puede llamar desde cualquier lado (hooks, dialogs, callbacks fuera de
 * componentes) sin pasar por Context -- mismo patron que el toast de shadcn,
 * con un store en modulo en vez de React Context.
 */
function toast(props: Toast) {
    const id = generarId();
    const cerrar = () => despachar({ type: 'CERRAR', toastId: id });

    despachar({
        type: 'AGREGAR',
        toast: {
            ...props,
            id,
            open: true,
            onOpenChange: (open) => {
                if (!open) cerrar();
            },
        },
    });

    return { id, cerrar };
}

function useToast() {
    const [estado, setEstado] = React.useState<Estado>(estadoEnMemoria);

    React.useEffect(() => {
        listeners.push(setEstado);
        return () => {
            const indice = listeners.indexOf(setEstado);
            if (indice > -1) listeners.splice(indice, 1);
        };
    }, []);

    return { ...estado, toast, cerrar: (toastId?: string) => despachar({ type: 'CERRAR', toastId }) };
}

export { toast, useToast };
