<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Telegrama extends Model
{
    use HasFactory;
    protected $table = 'telegramas';

    protected $fillable = [
        'mesa_id',
        'lista_id',
        'votos_diputados',
        'votos_senadores',
        'blancos',
        'nulos',
        'recurridos',
        'usuario',
    ];

    protected $casts = [
        'votos_diputados' => 'integer',
        'votos_senadores' => 'integer',
        'blancos' => 'integer',
        'nulos' => 'integer',
        'recurridos' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con la mesa electoral
     */
    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class);
    }

    /**
     * Relación con la lista electoral
     */
    public function lista(): BelongsTo
    {
        return $this->belongsTo(Lista::class);
    }

    /**
     * Calcula el total de votos del telegrama
     */
    public function totalVotos(): int
    {
        return $this->votos_diputados + $this->votos_senadores +
               $this->blancos + $this->nulos + $this->recurridos;
    }
}
