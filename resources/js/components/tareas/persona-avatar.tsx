import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
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

/** Avatar circular con iniciales + tooltip con el nombre. `destacado` marca al responsable dentro de un grupo de personas. */
export function PersonaAvatar({ nombre, destacado = false, className }: { nombre: string; destacado?: boolean; className?: string }) {
    return (
        <TooltipProvider>
            <Tooltip>
                <TooltipTrigger asChild>
                    <Avatar className={cn('size-8 border-2 border-background', destacado && 'ring-2 ring-verde-5', className)}>
                        <AvatarFallback className="bg-gris-2 text-xs text-white">{iniciales(nombre)}</AvatarFallback>
                    </Avatar>
                </TooltipTrigger>
                <TooltipContent>
                    {nombre}
                    {destacado ? ' (Responsable)' : ''}
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    );
}
