<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Rol extends Model {
    protected $connection = "usuarios";
    protected $table = "roles";
    public $timestamps = false;

    protected $fillable = [
        "nombre",
    ];

    public function usuarios(): BelongsToMany {
        return $this->belongsToMany(User::class, "usuarios_tienen_roles", "id_rol", "id_usuario");
    }

    public function permisos(): BelongsToMany {
        return $this->belongsToMany(Permiso::class, "roles_tienen_permisos", "id_rol", "id_permiso");
    }
}
