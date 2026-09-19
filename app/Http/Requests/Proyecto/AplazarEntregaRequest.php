<?php

namespace App\Http\Requests\Proyecto;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Aplazar la entrega de un Proyecto (pedido de AVA Montajes, reunion
 * 15-09-2026): solo corre fecha_termino hacia adelante -- corregir una fecha
 * hacia atras por error sigue siendo trabajo de "Editar proyecto"
 * (ActualizarProyectoRequest). Motivo obligatorio, mismo criterio que
 * RetrocederTareaRequest.
 */
class AplazarEntregaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "fecha_termino" => ["required", "date", "after:{$this->route('proyecto')->fecha_termino}"],
            "motivo" => ["required", "string"],
        ];
    }
}
