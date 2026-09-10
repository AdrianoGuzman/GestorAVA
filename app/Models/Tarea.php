<?php

namespace App\Models;

use App\Enums\EstadoTarea;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tarea extends Model {
    use HasFactory;

    protected $connection = "usuarios";
    protected $table = "tareas";

    protected $fillable = [
        "titulo",
        "descripcion",
        "responsable_id",
        "creador_id",
        "unidad_organizacional_id",
        "fecha_inicio",
        "fecha_compromiso",
        "estado",
        "esta_atrasada",
        "tarea_padre_id",
        "motivo_cancelacion",
        "fecha_cancelacion",
    ];

    protected $casts = [
        "estado" => EstadoTarea::class,
        "esta_atrasada" => "boolean",
        "fecha_inicio" => "date",
        "fecha_compromiso" => "date",
        "fecha_cancelacion" => "datetime",
    ];

    public function responsable(): BelongsTo {
        return $this->belongsTo(User::class, "responsable_id");
    }

    public function creador(): BelongsTo {
        return $this->belongsTo(User::class, "creador_id");
    }

    public function unidadOrganizacional(): BelongsTo {
        return $this->belongsTo(UnidadOrganizacional::class, "unidad_organizacional_id");
    }

    public function colaboradores(): BelongsToMany {
        return $this->belongsToMany(User::class, "colaboradores_tarea", "tarea_id", "usuario_id");
    }

    public function tareaPadre(): BelongsTo {
        return $this->belongsTo(self::class, "tarea_padre_id");
    }

    public function tareasHijas(): HasMany {
        return $this->hasMany(self::class, "tarea_padre_id");
    }

    public function checklistItems(): HasMany {
        return $this->hasMany(ChecklistItem::class, "tarea_id");
    }

    public function historial(): HasMany {
        return $this->hasMany(HistorialTarea::class, "tarea_id");
    }

    public function notificaciones(): HasMany {
        return $this->hasMany(Notificacion::class, "tarea_id");
    }
}
