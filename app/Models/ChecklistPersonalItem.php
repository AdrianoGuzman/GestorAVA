<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * "Mi checklist": guia personal y privada dentro de una tarea. Distinta del
 * checklist compartido de RF-23 (ChecklistItem, de Jeremy) -- no bloquea
 * nada, no la ve nadie mas que su dueño.
 */
class ChecklistPersonalItem extends Model
{
    use HasFactory;

    protected $connection = "usuarios";

    protected $table = "checklist_personal_items";

    protected $fillable = [
        "tarea_id",
        "usuario_id",
        "texto",
        "completado",
    ];

    protected $casts = [
        "completado" => "boolean",
    ];

    public function tarea(): BelongsTo
    {
        return $this->belongsTo(Tarea::class, "tarea_id");
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, "usuario_id");
    }
}
