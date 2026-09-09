<?php

namespace App\Enums;

enum TipoNotificacion: string
{
    case Delegacion = 'delegacion';
    case Retroceso = 'retroceso';
    case Rechazo = 'rechazo';
    case Cancelacion = 'cancelacion';
    case DependenciaCreada = 'dependencia_creada';
}
