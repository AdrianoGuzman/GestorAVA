<?php

namespace App\Enums;

enum TipoEventoProyecto: string
{
    case Creacion = 'creacion';
    case ProyectoEditado = 'proyecto_editado';
    case SeccionCreada = 'seccion_creada';
    case SeccionEditada = 'seccion_editada';
    case EntregaAplazada = 'entrega_aplazada';

    /** Mismo texto que ETIQUETAS_EVENTO en resources/js/components/proyectos/historial-proyecto-timeline.tsx. */
    public function label(): string
    {
        return match ($this) {
            self::Creacion => 'Creó el proyecto',
            self::ProyectoEditado => 'Editó el proyecto',
            self::SeccionCreada => 'Agregó una sección',
            self::SeccionEditada => 'Editó una sección',
            self::EntregaAplazada => 'Aplazó la entrega',
        };
    }
}
