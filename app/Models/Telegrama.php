<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Telegrama extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;
    protected $table = 'telegramas';

    protected $fillable = [
        'mesa_id',
        'blancos',
        'nulos',
        'recurridos',
        'usuario',
        'user_id',
    ];

    protected $casts = [
        'blancos' => 'integer',
        'nulos' => 'integer',
        'recurridos' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacion con la mesa electoral
     */
    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class);
    }

    /**
     * Relacion con los votos por lista
     */
    public function votos(): HasMany
    {
        return $this->hasMany(TelegramaVoto::class);
    }

    /**
     * Calcula el total de votos del telegrama (suma de todas las listas + blancos/nulos/recurridos)
     */
    public function totalVotos(): int
    {
        $votosListas = $this->votos()->sum('votos_diputados') + $this->votos()->sum('votos_senadores');
        return $votosListas + $this->blancos + $this->nulos + $this->recurridos;
    }

    /**
     * Calcula total de votos de diputados
     */
    public function totalVotosDiputados(): int
    {
        return $this->votos()->sum('votos_diputados');
    }

    /**
     * Calcula total de votos de senadores
     */
    public function totalVotosSenadores(): int
    {
        return $this->votos()->sum('votos_senadores');
    }
}
