<?php

namespace App\Models;

use App\Enums\TipoEvento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialTarea extends Model {
    protected $connection = "usuarios";
    protected $table = "historial_tareas";
    public $timestamps = false;
    const UPDATED_AT = null;

    protected $fillable = [
        "tarea_id",
        "tipo_evento",
        "usuario_id",
        "datos_evento",
    ];

    protected $casts = [
        "tipo_evento" => TipoEvento::class,
        "datos_evento" => "array",
        "created_at" => "datetime",
    ];

    public function tarea(): BelongsTo {
        return $this->belongsTo(Tarea::class, "tarea_id");
    }

    public function usuario(): BelongsTo {
        return $this->belongsTo(User::class, "usuario_id");
    }
}
