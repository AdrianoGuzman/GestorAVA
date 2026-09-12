<?php

namespace App\Http\Requests\Usuario;

use App\Enums\NivelJerarquico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * RNF-08: edicion minima de un usuario existente -- solo nivel jerarquico
 * y unidad organizacional, que es lo que pide la tarea ("crear/editar
 * usuarios con su nivel y unidad"). El resto de sus datos (nombre, cargo,
 * credenciales) no se edita aqui, eso es la administracion completa de
 * Fase 2.
 */
class EditarNivelYUnidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "nivel_jerarquico" => ["required", new Enum(NivelJerarquico::class)],
            // Prefijo "usuarios." explicito: "exists" resuelve la tabla
            // contra config('database.default'), no contra la conexion del
            // modelo -- si el .env de quien corre esto tiene DB_CONNECTION
            // distinto de "usuarios", sin el prefijo no encuentra la tabla.
            "unidad_organizacional_id" => ["required", "integer", "exists:usuarios.unidades_organizacionales,id"],
        ];
    }
}
