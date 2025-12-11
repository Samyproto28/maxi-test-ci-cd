<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provincia extends Model
{
    use HasFactory;
    protected $table = 'provincias';

    protected $fillable = ['nombre', 'codigo'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function listas(): HasMany
    {
        return $this->hasMany(Lista::class);
    }

    public function candidatos(): HasMany
    {
        return $this->hasMany(Candidato::class);
    }

    public function mesas(): HasMany
    {
        return $this->hasMany(Mesa::class);
    }
}
