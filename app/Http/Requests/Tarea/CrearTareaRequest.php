<?php

namespace App\Http\Requests\Tarea;

use App\Enums\PrioridadTarea;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

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

        // Quien crea define la prioridad; si no llega (ej. una llamada
        // directa al servicio sin pasar por HTTP), Media es un default
        // razonable en vez de rechazar la peticion.
        if (! $this->has("prioridad")) {
            $this->merge(["prioridad" => PrioridadTarea::Media->value]);
        }
    }

    public function rules(): array
    {
        return [
            "titulo" => ["required", "string", "max:255"],
            "descripcion" => ["nullable", "string"],
            "responsable_id" => ["required", "integer", "exists:users,id"],
            "prioridad" => ["required", new Enum(PrioridadTarea::class)],
            "fecha_inicio" => ["nullable", "date"],
            "fecha_compromiso" => [
                "required",
                "date",
                "after:today",
                function ($attribute, $value, $fail) {
                    $inicio = $this->input("fecha_inicio");
                    if ($inicio && $value < $inicio) {
                        $fail("La fecha de término no puede ser anterior a la fecha de inicio.");
                    }
                },
            ],
            "colaboradores" => ["sometimes", "array"],
            "colaboradores.*" => ["integer", "exists:users,id"],
        ];
    }
}
