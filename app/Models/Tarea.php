<?php

namespace App\Models;

use App\Enums\EstadoTarea;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tarea extends Model {
    use HasFactory;

    protected $connection = "usuarios";
    protected $table = "tareas";

    protected $appends = [
        "codigo",
    ];

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
        "recordatorio_vencimiento_enviado",
        "tarea_padre_id",
        "motivo_cancelacion",
        "fecha_cancelacion",
    ];

    protected $casts = [
        "estado" => EstadoTarea::class,
        "esta_atrasada" => "boolean",
        "recordatorio_vencimiento_enviado" => "boolean",
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

    public function adjuntos(): HasMany {
        return $this->hasMany(AdjuntoTarea::class, "tarea_id");
    }

    /**
     * Codigo secuencial global de la tarea (ej. TAR-0001), derivado
     * directamente del id: nunca se repite, no requiere columna ni
     * generacion aparte, y ordenar/buscar por codigo es tan simple como
     * ordenar/buscar por id.
     */
    protected function codigo(): Attribute {
        return Attribute::make(
            get: fn () => sprintf("TAR-%04d", $this->id),
        );
    }

    /**
     * Interpreta un texto de busqueda como codigo de tarea (ej. "TAR-0001",
     * "tar-1", o el numero solo) y devuelve el id correspondiente, o null si
     * no calza con el formato.
     */
    public static function idDesdeCodigo(string $texto): ?int {
        if (preg_match("/^(?:TAR-)?0*(\d+)$/i", trim($texto), $coincidencias) === 1) {
            return (int) $coincidencias[1];
        }

        return null;
    }
}
