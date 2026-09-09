<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistItem extends Model {
    protected $connection = "usuarios";
    protected $table = "checklist_items";

    protected $fillable = [
        "tarea_id",
        "texto",
        "completado",
        "dueno_id",
    ];

    protected $casts = [
        "completado" => "boolean",
    ];

    public function tarea(): BelongsTo {
        return $this->belongsTo(Tarea::class, "tarea_id");
    }

    public function dueno(): BelongsTo {
        return $this->belongsTo(User::class, "dueno_id");
    }
}
