<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    public const ACCION_CREATE = 'CREATE';
    public const ACCION_UPDATE = 'UPDATE';
    public const ACCION_DELETE = 'DELETE';
    public const ACCIONES = [self::ACCION_CREATE, self::ACCION_UPDATE, self::ACCION_DELETE];

    protected $table = 'auditoria';

    public $timestamps = false; // Solo usar created_at

    protected $fillable = [
        'tabla', 'registro_id', 'accion',
        'datos_anteriores', 'datos_nuevos', 'usuario'
    ];

    protected $casts = [
        'datos_anteriores' => 'array', // JSON to Array
        'datos_nuevos' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Método estático para registrar un cambio en auditoría
     */
    public static function registrar(
        string $tabla,
        int $registroId,
        string $accion,
        ?array $datosAnteriores,
        ?array $datosNuevos,
        string $usuario
    ): self {
        return self::create([
            'tabla' => $tabla,
            'registro_id' => $registroId,
            'accion' => $accion,
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => $datosNuevos,
            'usuario' => $usuario,
        ]);
    }
}
