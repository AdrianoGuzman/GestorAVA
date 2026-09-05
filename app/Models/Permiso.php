<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Permiso extends Model {
    protected $connection = "usuarios";
    protected $table = "permisos";
    public $timestamps = false;

    protected $fillable = [
        "nombre",
    ];

    public function roles(): BelongsToMany {
        return $this->belongsToMany(Rol::class, "roles_tienen_permisos", "id_permiso", "id_rol");
    }
}
