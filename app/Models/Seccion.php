<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Agrupa tareas de un Proyecto por objetivo (ej. "Objetivo 1: Cultura"), con
 * un peso (0-1) usado para ponderar el avance del proyecto completo -- ver
 * ProyectoController::avanceProyecto(). Decision de Franco (14-09-2026).
 */
class Seccion extends Model {
    use HasFactory;

    protected $connection = "usuarios";
    protected $table = "secciones";

    protected $fillable = [
        "proyecto_id",
        "nombre",
        "peso",
    ];

    protected $casts = [
        "peso" => "float",
    ];

    public function proyecto(): BelongsTo {
        return $this->belongsTo(Proyecto::class, "proyecto_id");
    }

    public function tareas(): HasMany {
        return $this->hasMany(Tarea::class, "seccion_id");
    }
}
