<?php

namespace App\Models;

use App\Enums\NivelJerarquico;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        "nivel_jerarquico",
        "unidad_organizacional_id",
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
        "nivel_jerarquico" => NivelJerarquico::class,
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

    public function unidadOrganizacional(): BelongsTo {
        return $this->belongsTo(UnidadOrganizacional::class, "unidad_organizacional_id");
    }

    public function tareasComoResponsable(): HasMany {
        return $this->hasMany(Tarea::class, "responsable_id");
    }

    public function tareasComoCreador(): HasMany {
        return $this->hasMany(Tarea::class, "creador_id");
    }

    public function tareasComoColaborador(): BelongsToMany {
        return $this->belongsToMany(Tarea::class, "colaboradores_tarea", "usuario_id", "tarea_id");
    }

    public function notificacionesRecibidas(): HasMany {
        return $this->hasMany(Notificacion::class, "usuario_id");
    }
}
