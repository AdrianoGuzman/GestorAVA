<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Proyecto extends Model {
    protected $connection = "usuarios";
    protected $table = "proyectos";
    public $timestamps = false;

    protected $fillable = [
        "centro_costo",
        "nombre",
        "alias",
    ];

    public function usuarios(): BelongsToMany {
        return $this->belongsToMany(User::class, "usuarios_tienen_proyectos", "id_proyecto", "id_usuario");
    }
}
