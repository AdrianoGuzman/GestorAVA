<?php

namespace App\Http\Requests\Usuario;

use App\Enums\NivelJerarquico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * RNF-08: alta minima de usuario (nivel + unidad) para no depender de
 * tocar la BD a mano. La autorizacion (solo Directorio/Gerencia) se valida
 * en UsuarioService, no aqui, siguiendo el mismo patron que el resto de
 * los permisos de tareas.
 */
class CrearUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "nombre_1" => ["required", "string", "max:255"],
            "nombre_2" => ["required", "string", "max:255"],
            "apellido_1" => ["required", "string", "max:255"],
            "apellido_2" => ["required", "string", "max:255"],
            "cargo" => ["required", "string", "max:255"],
            "rut" => ["required", "string", "max:20", "unique:users,rut"],
            "email" => ["required", "string", "email", "max:255", "unique:users,email"],
            "password" => ["required", "string", "min:8"],
            "nivel_jerarquico" => ["required", new Enum(NivelJerarquico::class)],
            "unidad_organizacional_id" => ["required", "integer", "exists:unidades_organizacionales,id"],
        ];
    }
}
