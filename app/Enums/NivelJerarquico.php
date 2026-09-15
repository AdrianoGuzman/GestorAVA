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

    /**
     * Nombre neutro para mostrar en la UI (RN-01): son los niveles de la
     * jerarquia organizacional, no el cargo literal de la persona (ese es
     * el campo "cargo" del usuario, un dato aparte).
     */
    public function label(): string
    {
        return match ($this) {
            self::Directorio => 'Directorio',
            self::Gerencia => 'Gerencia',
            self::JefeArea => 'Jefe de área/obra',
            self::Asistente => 'Asistente',
        };
    }

    /**
     * RNF-08: solo Directorio y Gerencia administran la estructura
     * organizacional (alta de usuarios, nivel y unidad). El resto de la UI
     * de administracion queda para Fase 2.
     */
    public function puedeAdministrarEstructura(): bool
    {
        return match ($this) {
            self::Directorio, self::Gerencia => true,
            self::JefeArea, self::Asistente => false,
        };
    }

    /**
     * Baja de usuarios: mas sensible que el resto de la administracion
     * (alta/edicion), reservada solo a Directorio a diferencia de
     * puedeAdministrarEstructura() que tambien incluye Gerencia.
     */
    public function puedeEliminarUsuarios(): bool
    {
        return $this === self::Directorio;
    }

    /**
     * Quien puede crear/editar/cerrar un Proyecto (agrupa tareas de varias
     * unidades bajo una misma iniciativa). Franco (14-09-2026): por ahora
     * Directorio y Gerencia, igual que puedeAdministrarEstructura() -- pero
     * es una regla propia (no la reusa) porque puede terminar abriendose a
     * Jefe de Área tras la reunión con AVA sin tocar la administración de
     * usuarios/unidades.
     */
    public function puedeAdministrarProyectos(): bool
    {
        return match ($this) {
            self::Directorio, self::Gerencia => true,
            self::JefeArea, self::Asistente => false,
        };
    }
}
