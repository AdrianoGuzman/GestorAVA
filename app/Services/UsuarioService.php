<?php

namespace App\Services;

use App\Exceptions\PermisoDenegadoException;
use App\Models\User;

/**
 * RNF-08: administracion minima de usuarios (alta con nivel + unidad).
 * La UI de administracion completa queda para Fase 2 — aqui solo se cubre
 * lo indispensable para que login (RF-01/03) y "Mis tareas" funcionen.
 */
class UsuarioService
{
    public function crear(array $datos, User $actor): User
    {
        $this->verificarPermiso($actor);

        return User::create($datos);
    }

    /**
     * RNF-08: edicion minima -- solo nivel jerarquico y unidad, ver
     * EditarNivelYUnidadRequest.
     */
    public function actualizarNivelYUnidad(User $usuario, array $datos, User $actor): User
    {
        $this->verificarPermiso($actor);

        $usuario->update($datos);

        return $usuario;
    }

    private function verificarPermiso(User $actor): void
    {
        if (! ($actor->nivel_jerarquico?->puedeAdministrarEstructura() ?? false)) {
            throw new PermisoDenegadoException("No tienes permiso para administrar la estructura organizacional.");
        }
    }
}
