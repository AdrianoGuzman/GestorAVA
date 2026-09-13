import { buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import * as React from 'react';
import { DayPicker } from 'react-day-picker';

export type CalendarProps = React.ComponentProps<typeof DayPicker>;

function Calendar({ className, classNames, showOutsideDays = true, ...props }: CalendarProps) {
    return (
        <DayPicker
            showOutsideDays={showOutsideDays}
            className={cn('p-3', className)}
            classNames={{
                months: 'flex flex-col sm:flex-row gap-2',
                month: 'flex flex-col gap-4',
                month_caption: 'flex justify-center pt-1 relative items-center w-full',
                caption_label: 'text-sm font-medium text-foreground',
                nav: 'flex items-center justify-between absolute inset-x-1 top-1',
                button_previous: cn(
                    buttonVariants({ variant: 'outline' }),
                    'size-7 bg-transparent p-0 opacity-60 transition-all hover:opacity-100 active:scale-95',
                ),
                button_next: cn(
                    buttonVariants({ variant: 'outline' }),
                    'size-7 bg-transparent p-0 opacity-60 transition-all hover:opacity-100 active:scale-95',
                ),
                month_grid: 'w-full border-collapse',
                weekdays: 'flex',
                weekday: 'text-muted-foreground w-8 font-normal text-xs',
                week: 'flex w-full mt-1',
                day: 'text-center text-sm p-0 relative [&:has([aria-selected])]:bg-verde-2 first:[&:has([aria-selected])]:rounded-l-md last:[&:has([aria-selected])]:rounded-r-md focus-within:relative focus-within:z-20',
                day_button: cn(
                    buttonVariants({ variant: 'ghost' }),
                    'size-8 p-0 font-normal transition-all aria-selected:opacity-100 hover:bg-verde-1 active:scale-95',
                ),
                range_start: 'rounded-l-md',
                range_end: 'rounded-r-md',
                selected: 'bg-verde-5 text-gris-2 hover:bg-verde-5 hover:text-gris-2 focus:bg-verde-5 focus:text-gris-2 font-semibold rounded-md',
                /* Anillo en vez de relleno: si "hoy" ademas esta seleccionado, el
                   relleno solido de `selected` sigue siendo el protagonista en vez
                   de competir por el mismo fondo. */
                today: 'rounded-md font-semibold ring-1 ring-inset ring-verde-5 aria-selected:ring-0',
                outside: 'text-muted-foreground opacity-50',
                disabled: 'text-muted-foreground opacity-50',
                range_middle: 'aria-selected:bg-verde-2 aria-selected:text-gris-2',
                hidden: 'invisible',
                ...classNames,
            }}
            components={{
                Chevron: ({ orientation, ...rest }) =>
                    orientation === 'left' ? <ChevronLeft className="size-4" {...rest} /> : <ChevronRight className="size-4" {...rest} />,
            }}
            {...props}
        />
    );
}
Calendar.displayName = 'Calendar';

export { Calendar };
