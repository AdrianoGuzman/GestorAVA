<?php

namespace App\Http\Requests\Tarea;

use App\Enums\PrioridadTarea;
use App\Models\Proyecto;
use App\Models\Seccion;
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
            "evidencia_obligatoria" => ["sometimes", "boolean"],
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
                "after:today",
                function ($attribute, $value, $fail) {
                    $inicio = $this->input("fecha_inicio");
                    if ($inicio && $value < $inicio) {
                        $fail("La fecha de término no puede ser anterior a la fecha de inicio.");
                    }
                },
                function ($attribute, $value, $fail) {
                    $proyecto = Proyecto::find($this->input("proyecto_id"));
                    if ($proyecto?->fecha_termino && $value > $proyecto->fecha_termino->toDateString()) {
                        $fail("La fecha de término no puede ser posterior al término del proyecto ({$proyecto->fecha_termino->format('d-m-Y')}).");
                    }
                },
            ],
            "colaboradores" => ["sometimes", "array"],
            "colaboradores.*" => ["integer", "exists:users,id"],
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
