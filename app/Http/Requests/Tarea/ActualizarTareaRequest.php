<?php

namespace App\Http\Requests\Tarea;

use App\Enums\PrioridadTarea;
use App\Models\Proyecto;
use App\Models\Seccion;
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

        // Mismo criterio que prioridad: un cliente que todavia no conoce
        // este campo no debe borrar el proyecto/seccion ya asignados sin querer.
        if (! $this->has("proyecto_id")) {
            $tarea = $this->route("tarea");
            $this->merge(["proyecto_id" => $tarea?->proyecto_id]);
        }

        if (! $this->has("seccion_id")) {
            $tarea = $this->route("tarea");
            $this->merge(["seccion_id" => $tarea?->seccion_id]);
        }

        if (! $this->has("evidencia_obligatoria")) {
            $tarea = $this->route("tarea");
            $this->merge(["evidencia_obligatoria" => $tarea?->evidencia_obligatoria ?? false]);
        }
    }

    public function rules(): array
    {
        return [
            "titulo" => ["required", "string", "max:255"],
            "descripcion" => ["nullable", "string"],
            "fecha_inicio" => [
                "nullable",
                "date",
                function ($attribute, $value, $fail) {
                    $proyecto = Proyecto::find($this->input("proyecto_id"));
                    if ($proyecto?->fecha_inicio && $value < $proyecto->fecha_inicio->toDateString()) {
                        $fail("La fecha de inicio no puede ser anterior al inicio del proyecto ({$proyecto->fecha_inicio->format('d-m-Y')}).");
                    }
                },
            ],
            "fecha_compromiso" => [
                "required",
                "date",
                function ($attribute, $value, $fail) {
                    $proyecto = Proyecto::find($this->input("proyecto_id"));
                    if ($proyecto?->fecha_termino && $value > $proyecto->fecha_termino->toDateString()) {
                        $fail("La fecha de término no puede ser posterior al término del proyecto ({$proyecto->fecha_termino->format('d-m-Y')}).");
                    }
                },
            ],
            "prioridad" => ["required", new Enum(PrioridadTarea::class)],
            "evidencia_obligatoria" => ["required", "boolean"],
            "proyecto_id" => ["nullable", "integer", "exists:usuarios.proyectos,id"],
            "seccion_id" => [
                "nullable",
                "integer",
                "exists:usuarios.secciones,id",
                function ($attribute, $value, $fail) {
                    $seccion = Seccion::find($value);
                    if ($seccion && (int) $seccion->proyecto_id !== (int) $this->input("proyecto_id")) {
                        $fail("La sección no pertenece al proyecto elegido.");
                    }
                },
            ],
        ];
    }
}
