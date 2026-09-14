<?php

namespace App\Http\Requests\Checklist;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Sin "after:today" en fecha_limite a proposito, igual que
 * ActualizarTareaRequest: un item con fecha ya vencida conserva su fecha si
 * el usuario solo corrige el texto o el dueño, sin verse forzado a
 * moverla para poder guardar.
 */
class EditarChecklistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "texto" => ["required", "string", "max:255"],
            "dueno_id" => ["nullable", "integer", "exists:users,id"],
            "fecha_limite" => ["nullable", "date"],
        ];
    }
}
