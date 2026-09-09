<?php

namespace App\Enums;

enum NivelJerarquico: string
{
    case Directorio = 'directorio';
    case Gerencia = 'gerencia';
    case JefeArea = 'jefe_area';
    case Asistente = 'asistente';

    /**
     * Menor a mayor. Usado para RN-11 (superior directo) y RN-12
     * (excepcion: cualquier nivel estrictamente superior, no necesariamente directo).
     */
    public function rango(): int
    {
        return match ($this) {
            self::Asistente => 1,
            self::JefeArea => 2,
            self::Gerencia => 3,
            self::Directorio => 4,
        };
    }

    public function nivelSuperior(): ?self
    {
        return match ($this) {
            self::Asistente => self::JefeArea,
            self::JefeArea => self::Gerencia,
            self::Gerencia => self::Directorio,
            self::Directorio => null,
        };
    }

    public function esSuperiorA(self $otro): bool
    {
        return $this->rango() > $otro->rango();
    }
}
