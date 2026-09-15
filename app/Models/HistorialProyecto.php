<?php

namespace App\Models;

use App\Enums\TipoEventoProyecto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialProyecto extends Model {
    protected $connection = "usuarios";
    protected $table = "historial_proyectos";
    public $timestamps = false;
    const UPDATED_AT = null;

    protected $fillable = [
        "proyecto_id",
        "tipo_evento",
        "usuario_id",
        "datos_evento",
    ];

    protected $casts = [
        "tipo_evento" => TipoEventoProyecto::class,
        "datos_evento" => "array",
        "created_at" => "datetime",
    ];

    public function proyecto(): BelongsTo {
        return $this->belongsTo(Proyecto::class, "proyecto_id");
    }

    public function usuario(): BelongsTo {
        return $this->belongsTo(User::class, "usuario_id");
    }
}
