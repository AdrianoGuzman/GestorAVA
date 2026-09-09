<?php

namespace App\Models;

use App\Enums\TipoUnidad;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnidadOrganizacional extends Model {
    use HasFactory;

    protected $connection = "usuarios";
    protected $table = "unidades_organizacionales";
    public $timestamps = false;

    protected $fillable = [
        "nombre",
        "tipo",
        "unidad_padre_id",
    ];

    protected $casts = [
        "tipo" => TipoUnidad::class,
    ];

    public function unidadPadre(): BelongsTo {
        return $this->belongsTo(self::class, "unidad_padre_id");
    }

    public function unidadesHijas(): HasMany {
        return $this->hasMany(self::class, "unidad_padre_id");
    }

    public function usuarios(): HasMany {
        return $this->hasMany(User::class, "unidad_organizacional_id");
    }

    public function tareas(): HasMany {
        return $this->hasMany(Tarea::class, "unidad_organizacional_id");
    }
}
