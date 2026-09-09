<?php

namespace App\Models;

use App\Enums\TipoNotificacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model {
    protected $connection = "usuarios";
    protected $table = "notificaciones";
    public $timestamps = false;
    const UPDATED_AT = null;

    protected $fillable = [
        "usuario_id",
        "tarea_id",
        "tipo",
        "mensaje",
        "leida",
    ];

    protected $casts = [
        "tipo" => TipoNotificacion::class,
        "leida" => "boolean",
        "created_at" => "datetime",
    ];

    public function usuario(): BelongsTo {
        return $this->belongsTo(User::class, "usuario_id");
    }

    public function tarea(): BelongsTo {
        return $this->belongsTo(Tarea::class, "tarea_id");
    }
}
