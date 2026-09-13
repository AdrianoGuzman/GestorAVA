<?php

namespace App\Http\Requests\Tarea;

use App\Enums\PrioridadTarea;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Edicion de titulo/descripcion/fechas/prioridad de una tarea existente. No
 * incluye "after_or_equal:today" en fecha_compromiso a proposito: una tarea
 * ya atrasada conserva su fecha vencida si el usuario solo corrige el
 * titulo, sin verse forzado a mover la fecha para poder guardar.
 */
class ActualizarTareaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Si no se manda prioridad (ej. un cliente antiguo, o un test que
        // aun no la conoce), se conserva la que ya tenia la tarea en vez de
        // exigirla en cada edicion.
        if (! $this->has("prioridad")) {
            $tarea = $this->route("tarea");
            $this->merge(["prioridad" => $tarea?->prioridad?->value ?? PrioridadTarea::Media->value]);
        }
    }

    public function rules(): array
    {
        return [
            "titulo" => ["required", "string", "max:255"],
            "descripcion" => ["nullable", "string"],
            "fecha_inicio" => ["nullable", "date"],
            "fecha_compromiso" => ["required", "date"],
            "prioridad" => ["required", new Enum(PrioridadTarea::class)],
        ];
    }
}
