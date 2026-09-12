<?php

namespace App\Services;

use App\Exceptions\PermisoDenegadoException;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

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

    /**
     * Baja de usuario (antes diferida a Fase 2). Bloquea auto-eliminacion y
     * traduce la violacion de FK (tareas/roles que aun referencian al
     * usuario, ON DELETE RESTRICT) a un mensaje entendible en vez de un 500.
     */
    public function eliminar(User $usuario, User $actor): void
    {
        if (! ($actor->nivel_jerarquico?->puedeEliminarUsuarios() ?? false)) {
            throw new PermisoDenegadoException("Solo Directorio puede eliminar usuarios.");
        }

        if ($usuario->id === $actor->id) {
            throw ValidationException::withMessages([
                "usuario" => "No puedes eliminar tu propia cuenta.",
            ]);
        }

        try {
            $usuario->delete();
        } catch (QueryException) {
            throw ValidationException::withMessages([
                "usuario" => "No se puede eliminar: el usuario todavia tiene tareas asociadas (como responsable o creador).",
            ]);
        }
    }

    private function verificarPermiso(User $actor): void
    {
        if (! ($actor->nivel_jerarquico?->puedeAdministrarEstructura() ?? false)) {
            throw new PermisoDenegadoException("No tienes permiso para administrar la estructura organizacional.");
        }
    }
}
