import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { CalendarDays, X } from 'lucide-react';
import { useState } from 'react';
import type { Matcher } from 'react-day-picker';

/** yyyy-MM-dd en horario local -- evita el corrimiento de un dia que da
 *  `new Date('yyyy-MM-dd')` (lo interpreta como UTC medianoche). */
export function fechaAString(fecha: Date): string {
    const anio = fecha.getFullYear();
    const mes = String(fecha.getMonth() + 1).padStart(2, '0');
    const dia = String(fecha.getDate()).padStart(2, '0');
    return `${anio}-${mes}-${dia}`;
}

export function stringAFecha(valor: string): Date {
    const [anio, mes, dia] = valor.split('-').map(Number);
    return new Date(anio, mes - 1, dia);
}

/**
 * Clases compartidas para un "circulo de opcion" (fecha, dueño, etc.) que
 * vive en la misma linea de un item/fila: punteado y semitransparente
 * cuando no tiene valor (para no competir visualmente con el contenido),
 * solido con los colores de marca cuando ya tiene uno asignado.
 */
export function circuloOpcionClasses(lleno: boolean): string {
    return cn(
        'flex size-7 shrink-0 items-center justify-center rounded-full border transition-all',
        lleno
            ? 'border-verde-3 bg-verde-2 text-gris-2 opacity-100'
            : 'border-dashed border-gris-1/50 text-gris-1 opacity-60 hover:opacity-100 hover:border-verde-6 hover:text-verde-6',
    );
}

/**
 * Boton + Popover con calendario propio (react-day-picker), en vez del
 * input nativo type="date": ese abre el calendario del navegador/SO, que
 * no se puede vestir con la paleta de AVA y se ve distinto en cada
 * navegador. Con esto el popup respeta la marca en todos lados.
 *
 * `compact`: circulo punteado con solo el icono (para vivir en la misma
 * linea de un item, ej. la fila de "agregar item" de un checklist), en vez
 * del boton rectangular con label de texto.
 */
export function DatePickerButton({
    label,
    valor,
    onChange,
    className,
    soloFuturo,
    minFecha,
    compact = false,
}: {
    label: string;
    valor: string;
    onChange: (valor: string) => void;
    className?: string;
    /** Deshabilita hoy y fechas pasadas en el calendario (para fechas de compromiso). */
    soloFuturo?: boolean;
    /** Ademas de soloFuturo, no permite elegir un dia anterior a esta fecha "yyyy-MM-dd" (ej. termino no puede ser antes que inicio). */
    minFecha?: string;
    compact?: boolean;
}) {
    const [abierto, setAbierto] = useState(false);
    const fechaFormateada = valor ? stringAFecha(valor).toLocaleDateString('es-CL') : null;

    const limites: Matcher[] = [];
    if (soloFuturo) {
        limites.push({ before: new Date(new Date().setHours(24, 0, 0, 0)) });
    }
    if (minFecha) {
        limites.push({ before: stringAFecha(minFecha) });
    }

    return (
        <Popover open={abierto} onOpenChange={setAbierto}>
            <PopoverTrigger asChild>
                {compact ? (
                    <button type="button" title={fechaFormateada ?? label} className={cn(circuloOpcionClasses(!!valor), className)}>
                        <CalendarDays className="size-3.5" />
                    </button>
                ) : (
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className={cn('h-8 gap-1.5 text-xs font-normal', !valor && 'text-muted-foreground', className)}
                    >
                        <CalendarDays className="size-3.5" />
                        {fechaFormateada ?? label}
                    </Button>
                )}
            </PopoverTrigger>
            <PopoverContent className="w-auto p-0" align="start">
                <Calendar
                    mode="single"
                    selected={valor ? stringAFecha(valor) : undefined}
                    disabled={limites.length > 0 ? limites : undefined}
                    onSelect={(fecha) => {
                        onChange(fecha ? fechaAString(fecha) : '');
                        setAbierto(false);
                    }}
                />
                {valor && (
                    <button
                        type="button"
                        onClick={() => {
                            onChange('');
                            setAbierto(false);
                        }}
                        className="flex w-full items-center justify-center gap-1.5 border-t border-border py-2 text-xs text-muted-foreground hover:text-rojo-1"
                    >
                        <X className="size-3" /> Quitar fecha
                    </button>
                )}
            </PopoverContent>
        </Popover>
    );
}
