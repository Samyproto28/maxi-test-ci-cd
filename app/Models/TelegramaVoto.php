<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramaVoto extends Model
{
    use HasFactory;

    protected $table = 'telegrama_votos';

    protected $fillable = [
        'telegrama_id',
        'lista_id',
        'votos_diputados',
        'votos_senadores',
    ];

    protected $casts = [
        'votos_diputados' => 'integer',
        'votos_senadores' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacion con el telegrama
     */
    public function telegrama(): BelongsTo
    {
        return $this->belongsTo(Telegrama::class);
    }

    /**
     * Relacion con la lista electoral
     */
    public function lista(): BelongsTo
    {
        return $this->belongsTo(Lista::class);
    }
}
