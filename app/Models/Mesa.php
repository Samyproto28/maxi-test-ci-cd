<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mesa extends Model
{
    use HasFactory;
    protected $table = 'mesas';

    protected $fillable = [
        'id_mesa',
        'provincia_id',
        'circuito',
        'establecimiento',
        'electores'
    ];

    protected $casts = [
        'electores' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function provincia(): BelongsTo
    {
        return $this->belongsTo(Provincia::class);
    }

    public function telegramas(): HasMany
    {
        return $this->hasMany(Telegrama::class);
    }

    // Método helper para obtener total de votos cargados
    public function totalVotosCargados(): int
    {
        return $this->telegramas()->sum('votos_diputados') +
               $this->telegramas()->sum('votos_senadores') +
               $this->telegramas()->sum('blancos') +
               $this->telegramas()->sum('nulos') +
               $this->telegramas()->sum('recurridos');
    }
}
