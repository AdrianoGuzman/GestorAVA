<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class UsuarioTieneRol extends Model {
    protected $connection = "usuarios";
    protected $table = "usuarios_tienen_roles";
    public $timestamps = false;

    protected $fillable = [
        "id_usuario",
        "id_rol",
    ];

    public function usuario(): BelongsTo {
        return $this->belongsTo(User::class, "id_usuario");
    }

    public function rol(): BelongsTo {
        return $this->belongsTo(Rol::class, "id_rol");
    }
}
