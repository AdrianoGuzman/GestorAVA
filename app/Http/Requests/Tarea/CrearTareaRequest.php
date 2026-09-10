<?php

namespace App\Http\Requests\Tarea;

use Illuminate\Foundation\Http\FormRequest;

/**
 * RF-04 D1-D2: validacion de creacion de tarea.
 */
class CrearTareaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // D1.2: por defecto el responsable es quien crea la tarea.
        if (! $this->has("responsable_id")) {
            $this->merge(["responsable_id" => $this->user()->id]);
        }
    }

    public function rules(): array
    {
        return [
            "titulo" => ["required", "string", "max:255"],
            "descripcion" => ["nullable", "string"],
            "responsable_id" => ["required", "integer", "exists:users,id"],
            "fecha_inicio" => ["nullable", "date"],
            "fecha_compromiso" => ["required", "date", "after_or_equal:today"],
            "colaboradores" => ["sometimes", "array"],
            "colaboradores.*" => ["integer", "exists:users,id"],
        ];
    }
}
