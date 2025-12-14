<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Concepto extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla
     */
    protected $table = 'conceptos';

    /**
     * Los atributos que se pueden asignar masivamente
     */
    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'tipo',
        'activo',
        'orden',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos
     */
    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    /**
     * Valores por defecto
     */
    protected $attributes = [
        'tipo' => 'monetario',
        'activo' => 1,
        'orden' => 0,
    ];
}

