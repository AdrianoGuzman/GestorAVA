<?php

namespace App\Enums;

enum PrioridadTarea: string
{
    case Alta = 'alta';
    case Media = 'media';
    case Baja = 'baja';

    /**
     * Peso numerico para ordenar de mayor a menor urgencia (usado al listar
     * tareas, para que las de prioridad alta aparezcan primero dentro de un
     * mismo grupo/fecha).
     */
    public function peso(): int
    {
        return match ($this) {
            self::Alta => 3,
            self::Media => 2,
            self::Baja => 1,
        };
    }
}
