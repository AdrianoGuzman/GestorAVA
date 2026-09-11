import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { Check } from 'lucide-react';
import { useMemo, useState } from 'react';

export interface Persona {
    id: number;
    name: string;
    email: string;
}

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
 * Selector de personas estilo Trello: click para abrir, escribir para
 * buscar, click en una persona para elegirla. `onSelect` se llama con la
 * persona clickeada; el llamador decide si eso significa "agregar",
 * "sacar" (toggle) o "reemplazar" (selección única).
 */
export function PersonaPicker({
    trigger,
    personas,
    seleccionadosIds,
    onSelect,
    cerrarAlSeleccionar = false,
}: {
    trigger: React.ReactNode;
    personas: Persona[];
    seleccionadosIds: number[];
    onSelect: (persona: Persona) => void;
    cerrarAlSeleccionar?: boolean;
}) {
    const [open, setOpen] = useState(false);
    const [busqueda, setBusqueda] = useState('');

    const filtradas = useMemo(
        () => personas.filter((persona) => persona.name.toLowerCase().includes(busqueda.toLowerCase())),
        [personas, busqueda],
    );

    return (
        <Popover
            open={open}
            onOpenChange={(siguiente) => {
                setOpen(siguiente);
                if (!siguiente) setBusqueda('');
            }}
        >
            <PopoverTrigger asChild>{trigger}</PopoverTrigger>
            <PopoverContent className="w-72 p-0" align="start">
                <div className="border-b border-border p-2">
                    <Input value={busqueda} onChange={(e) => setBusqueda(e.target.value)} placeholder="Buscar persona..." autoFocus />
                </div>
                <div className="max-h-64 overflow-y-auto p-1">
                    {filtradas.length === 0 && <p className="p-3 text-sm text-muted-foreground">Sin resultados.</p>}
                    {filtradas.map((persona) => {
                        const seleccionada = seleccionadosIds.includes(persona.id);
                        return (
                            <button
                                key={persona.id}
                                type="button"
                                onClick={() => {
                                    onSelect(persona);
                                    if (cerrarAlSeleccionar) setOpen(false);
                                }}
                                className={cn(
                                    'flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm transition-colors hover:bg-verde-1',
                                    seleccionada && 'bg-verde-2',
                                )}
                            >
                                <Avatar className="size-6">
                                    <AvatarFallback className="bg-gris-2 text-[10px] text-white">{iniciales(persona.name)}</AvatarFallback>
                                </Avatar>
                                <span className="flex-1 truncate">{persona.name}</span>
                                {seleccionada && <Check className="size-4 text-verde-6" />}
                            </button>
                        );
                    })}
                </div>
            </PopoverContent>
        </Popover>
    );
}
