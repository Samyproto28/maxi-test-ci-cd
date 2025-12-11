<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use OwenIt\Auditing\Contracts\Auditable;

class Lista extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;
    // Constantes para valores ENUM
    public const CARGO_DIPUTADOS = 'DIPUTADOS';
    public const CARGO_SENADORES = 'SENADORES';
    public const CARGOS = [self::CARGO_DIPUTADOS, self::CARGO_SENADORES];

    protected $table = 'listas';

    protected $fillable = ['nombre', 'alianza', 'provincia_id', 'cargo'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function provincia(): BelongsTo
    {
        return $this->belongsTo(Provincia::class);
    }

    public function candidatos(): HasMany
    {
        return $this->hasMany(Candidato::class)->orderBy('orden');
    }

    public function telegramas(): HasMany
    {
        return $this->hasMany(Telegrama::class);
    }
}
