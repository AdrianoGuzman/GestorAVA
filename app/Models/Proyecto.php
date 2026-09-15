<?php

namespace App\Models;

use App\Enums\EstadoProyecto;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Agrupa tareas de una o varias unidades organizacionales bajo una misma
 * iniciativa estrategica -- reemplaza el "Proyecto" anterior (centro de
 * costos heredado de otro sistema de AVA, sin uso real, ver migracion
 * 2026_09_14_100000_drop_legacy_proyectos_tables). Decision de Franco
 * (14-09-2026), documentada en ContextoProgramacion.md.
 */
class Proyecto extends Model {
    use HasFactory;

    protected $connection = "usuarios";
    protected $table = "proyectos";

    protected $fillable = [
        "nombre",
        "descripcion",
        "creador_id",
        "estado",
        "fecha_inicio",
        "fecha_termino",
    ];

    protected $casts = [
        "estado" => EstadoProyecto::class,
        "fecha_inicio" => "date",
        "fecha_termino" => "date",
    ];

    public function creador(): BelongsTo {
        return $this->belongsTo(User::class, "creador_id");
    }

    public function tareas(): HasMany {
        return $this->hasMany(Tarea::class, "proyecto_id");
    }

    public function secciones(): HasMany {
        return $this->hasMany(Seccion::class, "proyecto_id");
    }

    public function historial(): HasMany {
        return $this->hasMany(HistorialProyecto::class, "proyecto_id");
    }
}
