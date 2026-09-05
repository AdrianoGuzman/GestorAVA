<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class RolTienePermiso extends Model {
    protected $connection = "usuarios";
    protected $table = "roles_tienen_permisos";
    public $timestamps = false;

    protected $fillable = [
        "id_rol",
        "id_permiso",
    ];

    public function rol(): BelongsTo {
        return $this->belongsTo(Rol::class, "id_rol");
    }

    public function permiso(): BelongsTo {
        return $this->belongsTo(Permiso::class, "id_permiso");
    }
}
