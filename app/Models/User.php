<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


class User extends Authenticatable {
    use HasFactory, Notifiable;
    protected $connection = "usuarios";
    protected $table = "users";
    public $timestamps = false;

    protected $fillable = [
        "nombre_1",
        "nombre_2",
        "apellido_1",
        "apellido_2",
        "cargo",
        "rut",
        "email",
        "password",
    ];

    protected $hidden = [
        "password",
        "remember_token",
    ];

    protected $appends = [
        "name",
    ];

    protected $casts = [
        "email_verified_at" => "datetime",
        "password" => "hashed",
    ];

    public function getNameAttribute(): string {
        return trim(implode(" ", [
            $this->nombre_1,
            $this->nombre_2,
            $this->apellido_1,
            $this->apellido_2,
        ]));
    }

    public function roles(): BelongsToMany {
        return $this->belongsToMany(Rol::class, "usuarios_tienen_roles", "id_usuario", "id_rol");
    }

    public function proyectos(): BelongsToMany {
        return $this->belongsToMany(Proyecto::class, "usuarios_tienen_proyectos", "id_usuario", "id_proyecto");
    }
}