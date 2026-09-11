<?php

namespace App\Enums;

enum EstadoTarea: string
{
    case Pendiente = 'pendiente';
    case EnProgreso = 'en_progreso';
    case Completada = 'completada';
    case Cancelada = 'cancelada';

    /**
     * Transiciones válidas centralizadas (state machine explícita, ver
     * decisión arquitectónica en Contexto_Claude_Code_Sprint1.md sección 1).
     * No existe un estado "Rechazada": informar que una tarea está mal
     * definida no interrumpe el trabajo, solo notifica (ver RF-13
     * rediseñado en ReporteProblemaService). Completada y Cancelada son
     * terminales.
     */
    public function puedeTransicionarA(self $destino): bool
    {
        return match ($this) {
            self::Pendiente => in_array($destino, [self::EnProgreso, self::Completada, self::Cancelada], true),
            self::EnProgreso => in_array($destino, [self::Pendiente, self::Completada, self::Cancelada], true),
            self::Completada, self::Cancelada => false,
        };
    }
}
