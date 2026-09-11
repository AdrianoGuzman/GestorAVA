<?php

namespace App\Enums;

enum TipoNotificacion: string
{
    case Delegacion = 'delegacion';
    case Retroceso = 'retroceso';
    case ProblemaReportado = 'problema_reportado';
    case Cancelacion = 'cancelacion';
    case DependenciaCreada = 'dependencia_creada';
}
