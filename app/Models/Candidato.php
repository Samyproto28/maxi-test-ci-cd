<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class Candidato extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable, HasFactory;

    public const CARGO_DIPUTADOS = 'DIPUTADOS';
    public const CARGO_SENADORES = 'SENADORES';
    public const CARGOS = [self::CARGO_DIPUTADOS, self::CARGO_SENADORES];

    protected $table = 'candidatos';

    protected $fillable = [
        'nombre',
        'lista_id',
        'provincia_id',
        'cargo',
        'orden',
        'observaciones',
    ];

    protected $casts = [
        'orden' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function lista(): BelongsTo
    {
        return $this->belongsTo(Lista::class);
    }

    public function provincia(): BelongsTo
    {
        return $this->belongsTo(Provincia::class);
    }
}
