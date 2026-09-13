<?php

namespace App\Http\Requests\Tarea;

use App\Enums\PrioridadTarea;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * RF-18: duplicar copia responsable y unidad organizacional (el server los
 * toma de la tarea origen, no se envian aqui); titulo, descripcion, fechas
 * y prioridad quedan editables (prioridad se prellena con la de la tarea
 * origen si no se manda explicitamente, ver prepareForValidation()).
 */
class DuplicarTareaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has("prioridad")) {
            $origen = $this->route("tarea");
            $this->merge(["prioridad" => $origen?->prioridad?->value ?? PrioridadTarea::Media->value]);
        }
    }

    public function rules(): array
    {
        return [
            "titulo" => ["required", "string", "max:255"],
            "descripcion" => ["nullable", "string"],
            "fecha_inicio" => ["nullable", "date"],
            "fecha_compromiso" => ["required", "date", "after:today"],
            "prioridad" => ["required", new Enum(PrioridadTarea::class)],
        ];
    }
}
