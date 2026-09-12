<?php

namespace App\Http\Requests\Usuario;

use App\Enums\NivelJerarquico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * RNF-08: alta minima de usuario (nivel + unidad) para no depender de
 * tocar la BD a mano. La autorizacion (solo Directorio/Gerencia) se valida
 * en UsuarioService, no aqui, siguiendo el mismo patron que el resto de
 * los permisos de tareas.
 *
 * Nota: unique/exists van con el prefijo "usuarios." explicito porque esas
 * reglas resuelven la tabla contra config('database.default'), no contra
 * la conexion del modelo -- si el .env de quien corre esto tiene
 * DB_CONNECTION distinto de "usuarios" (ej. "pgsql" a secas), sin el
 * prefijo buscan la tabla en el schema "public" y no la encuentran.
 */
class CrearUsuarioRequest extends FormRequest
{
    /**
     * Lista cerrada de cargos de ejemplo del rubro de AVA Montajes.
     * Mantener sincronizada con CARGOS_AVA en resources/js/types/usuario.ts.
     */
    private const CARGOS_AVA = [
        "Gerente de Operaciones",
        "Jefe de Obra",
        "Supervisor de Obra",
        "Ingeniero de Proyectos",
        "Prevencionista de Riesgos",
        "Administrativo de Obra",
        "Bodeguero",
        "Maestro Electricista",
        "Electricista",
        "Soldador",
        "Ayudante de Montaje",
    ];

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
            "cargo" => ["required", "string", Rule::in(self::CARGOS_AVA)],
            "rut" => ["required", "string", "regex:/^\d{1,8}-[\dkK]$/", "unique:usuarios.users,rut"],
            "email" => ["required", "string", "email", "max:255", "unique:usuarios.users,email"],
            "password" => ["required", "string", "min:8"],
            "nivel_jerarquico" => ["required", new Enum(NivelJerarquico::class)],
            "unidad_organizacional_id" => ["required", "integer", "exists:usuarios.unidades_organizacionales,id"],
        ];
    }
}
