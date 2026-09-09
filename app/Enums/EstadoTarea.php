<?php

namespace App\Enums;

enum EstadoTarea: string
{
    case Pendiente = 'pendiente';
    case EnProgreso = 'en_progreso';
    case Completada = 'completada';
    case Rechazada = 'rechazada';
    case Cancelada = 'cancelada';

    /**
     * Transiciones válidas centralizadas (state machine explícita, ver
     * decisión arquitectónica en Contexto_Claude_Code_Sprint1.md sección 1).
     * Rechazada solo vuelve a Pendiente vía reasignación (RF-05/RF-13);
     * Completada y Cancelada son terminales.
     */
    public function puedeTransicionarA(self $destino): bool
    {
        return match ($this) {
            self::Pendiente => in_array($destino, [self::EnProgreso, self::Completada, self::Rechazada, self::Cancelada], true),
            self::EnProgreso => in_array($destino, [self::Pendiente, self::Completada, self::Rechazada, self::Cancelada], true),
            self::Rechazada => in_array($destino, [self::Pendiente, self::Cancelada], true),
            self::Completada, self::Cancelada => false,
        };
    }
}
