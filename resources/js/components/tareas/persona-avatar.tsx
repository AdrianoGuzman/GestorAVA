import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

function iniciales(nombre: string): string {
    return (
        nombre
            .split(' ')
            .filter(Boolean)
            .slice(0, 2)
            .map((parte) => parte[0]?.toUpperCase())
            .join('') || '?'
    );
}

/**
 * Avatar circular con iniciales. Al hacer click (estilo Asana) abre una
 * tarjeta con el nombre completo, el email y su rol -- `destacado` marca al
 * responsable dentro de un grupo de personas. `email`/`rol` son opcionales:
 * si no se pasan, esa fila simplemente no aparece en la tarjeta.
 */
export function PersonaAvatar({
    nombre,
    email,
    rol,
    destacado = false,
    className,
}: {
    nombre: string;
    email?: string;
    rol?: string;
    destacado?: boolean;
    className?: string;
}) {
    const etiquetaRol = rol ?? (destacado ? 'Responsable' : null);

    return (
        <Popover>
            <PopoverTrigger asChild>
                <button type="button" className="cursor-pointer rounded-full">
                    <Avatar className={cn('size-8 border-2 border-verde-3', destacado && 'ring-2 ring-verde-5', className)}>
                        <AvatarFallback className="bg-verde-2 text-xs font-semibold text-gris-2">{iniciales(nombre)}</AvatarFallback>
                    </Avatar>
                </button>
            </PopoverTrigger>
            <PopoverContent className="w-64" align="start">
                <div className="flex items-center gap-3">
                    <Avatar className="size-12 border-2 border-verde-3">
                        <AvatarFallback className="text-base font-semibold bg-verde-2 text-gris-2">{iniciales(nombre)}</AvatarFallback>
                    </Avatar>
                    <div className="min-w-0">
                        <p className="truncate font-semibold text-foreground">{nombre}</p>
                        {email && <p className="truncate text-sm text-muted-foreground">{email}</p>}
                    </div>
                </div>
                {etiquetaRol && (
                    <span className="mt-3 inline-flex items-center rounded-full border border-verde-3 bg-verde-2 px-2.5 py-0.5 text-xs font-medium text-gris-2">
                        {etiquetaRol}
                    </span>
                )}
            </PopoverContent>
        </Popover>
    );
}
