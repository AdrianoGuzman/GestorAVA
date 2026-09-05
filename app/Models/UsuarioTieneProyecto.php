<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class UsuarioTieneProyecto extends Model {
    protected $connection = "usuarios";
    protected $table = "usuarios_tienen_proyectos";
    public $timestamps = false;

    protected $fillable = [
        "id_usuario",
        "id_proyecto",
    ];

    public function usuario(): BelongsTo {
        return $this->belongsTo(User::class, "id_usuario");
    }

    public function proyecto(): BelongsTo {
        return $this->belongsTo(Proyecto::class, "id_proyecto");
    }
}
