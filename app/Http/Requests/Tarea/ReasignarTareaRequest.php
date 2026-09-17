<?php

namespace App\Http\Requests\Tarea;

use Illuminate\Foundation\Http\FormRequest;

/**
 * RF-05 (reasignacion) y RN-12 (excepcion por ausencia total).
 */
class ReasignarTareaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "nuevo_responsable_id" => ["required", "integer", "exists:users,id"],
            "mantener_como_colaborador" => ["sometimes", "boolean"],
            "es_excepcion" => ["sometimes", "boolean"],
            "motivo_excepcion" => ["nullable", "required_if:es_excepcion,true", "string"],
        ];
    }
}
