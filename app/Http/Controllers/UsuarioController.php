<?php

namespace App\Http\Controllers;

use App\Http\Requests\Usuario\CrearUsuarioRequest;
use App\Http\Requests\Usuario\EditarNivelYUnidadRequest;
use App\Models\UnidadOrganizacional;
use App\Models\User;
use App\Services\UsuarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * RNF-08: administracion minima de la estructura organizacional -- alta y
 * edicion de nivel jerarquico/unidad (Directorio y Gerencia, ver
 * NivelJerarquico::puedeAdministrarEstructura()), y baja de usuarios (mas
 * sensible, solo Directorio, ver puedeEliminarUsuarios()).
 * Gestion completa de la estructura organizacional (unidades) queda para Fase 2.
 */
class UsuarioController extends Controller
{
    public function __construct(private readonly UsuarioService $usuarioService)
    {
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()->nivel_jerarquico?->puedeAdministrarEstructura(), 403);

        return Inertia::render("administracion/usuarios/index", [
            "usuarios" => User::with("unidadOrganizacional")
                ->orderBy("apellido_1")
                ->get(["id", "nombre_1", "nombre_2", "apellido_1", "apellido_2", "email", "cargo", "nivel_jerarquico", "unidad_organizacional_id"]),
            "unidades" => UnidadOrganizacional::orderBy("nombre")->get(["id", "nombre", "tipo"]),
        ]);
    }

    public function store(CrearUsuarioRequest $request): RedirectResponse
    {
        $usuario = $this->usuarioService->crear($request->validated(), $request->user());

        return back()->with("success", "Usuario \"{$usuario->name}\" creado correctamente.");
    }

    public function update(EditarNivelYUnidadRequest $request, User $usuario): RedirectResponse
    {
        $usuario = $this->usuarioService->actualizarNivelYUnidad($usuario, $request->validated(), $request->user());

        return back()->with("success", "Usuario \"{$usuario->name}\" actualizado correctamente.");
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        $nombre = $usuario->name;

        $this->usuarioService->eliminar($usuario, $request->user());

        return back()->with("success", "Usuario \"{$nombre}\" eliminado correctamente.");
    }
}
