import { Toast, ToastClose, ToastDescription, ToastProvider, ToastTitle, ToastViewport } from '@/components/ui/toast';
import { useToast } from '@/hooks/use-toast';
import { CircleAlert, CircleCheckBig } from 'lucide-react';

const ICONO_POR_VARIANTE = {
    success: CircleCheckBig,
    destructive: CircleAlert,
} as const;

/** Vive una sola vez, montado en el layout principal (ver app-sidebar-layout.tsx) -- cualquier componente dispara un toast con toast() sin necesitar este arbol. */
export function Toaster() {
    const { toasts } = useToast();

    return (
        <ToastProvider>
            {toasts.map(({ id, title, description, variant, ...props }) => {
                const Icono = variant && variant in ICONO_POR_VARIANTE ? ICONO_POR_VARIANTE[variant as keyof typeof ICONO_POR_VARIANTE] : null;

                return (
                    <Toast key={id} variant={variant} {...props}>
                        <div className="flex flex-1 gap-3">
                            {Icono && <Icono data-toast-icon className="mt-0.5 size-5 shrink-0" />}
                            <div className="grid gap-1">
                                {title && <ToastTitle>{title}</ToastTitle>}
                                {description && <ToastDescription>{description}</ToastDescription>}
                            </div>
                        </div>
                        <ToastClose />
                    </Toast>
                );
            })}
            <ToastViewport />
        </ToastProvider>
    );
}
