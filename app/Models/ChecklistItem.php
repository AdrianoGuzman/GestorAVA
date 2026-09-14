<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistItem extends Model {
    use HasFactory;

    protected $connection = "usuarios";
    protected $table = "checklist_items";

    protected $fillable = [
        "tarea_id",
        "texto",
        "completado",
        "dueno_id",
        "fecha_limite",
    ];

    protected $casts = [
        "completado" => "boolean",
        "fecha_limite" => "date",
    ];

    public function tarea(): BelongsTo {
        return $this->belongsTo(Tarea::class, "tarea_id");
    }

    public function dueno(): BelongsTo {
        return $this->belongsTo(User::class, "dueno_id");
    }
}
