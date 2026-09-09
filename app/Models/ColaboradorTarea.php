<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ColaboradorTarea extends Model {
    protected $connection = "usuarios";
    protected $table = "colaboradores_tarea";
    public $timestamps = false;

    protected $fillable = [
        "tarea_id",
        "usuario_id",
    ];

    public function tarea(): BelongsTo {
        return $this->belongsTo(Tarea::class, "tarea_id");
    }

    public function usuario(): BelongsTo {
        return $this->belongsTo(User::class, "usuario_id");
    }
}
