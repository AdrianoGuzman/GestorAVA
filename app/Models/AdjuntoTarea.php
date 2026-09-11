<?php

namespace App\Models;

use App\Enums\CategoriaAdjunto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdjuntoTarea extends Model
{
    protected $connection = "usuarios";

    protected $table = "adjuntos_tarea";

    public $timestamps = false;

    const UPDATED_AT = null;

    protected $fillable = [
        "tarea_id",
        "usuario_id",
        "nombre_original",
        "ruta",
        "mime_type",
        "tamano_bytes",
        "categoria",
    ];

    protected $casts = [
        "created_at" => "datetime",
        "categoria" => CategoriaAdjunto::class,
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
