<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TarifaPeriodo extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla
     */
    protected $table = 'tarifas_periodos';

    /**
     * Clave primaria compuesta
     * Nota: Laravel no soporta completamente claves primarias compuestas en Eloquent
     * Se usa concepto_id como clave primaria para referencia, pero las consultas
     * deben usar where() con ambos campos para obtener registros únicos
     */
    protected $primaryKey = 'concepto_id';

    /**
     * Indica si el modelo debe usar timestamps automáticos
     */
    public $timestamps = false;

    /**
     * Indica si las claves primarias son incrementales
     */
    public $incrementing = false;

    /**
     * Los atributos que se pueden asignar masivamente
     */
    protected $fillable = [
        'concepto_id',
        'periodo',
        'monto',
        'tipo',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos
     */
    protected $casts = [
        'monto' => 'decimal:2',
    ];

    /**
     * Valores por defecto
     */
    protected $attributes = [
        'tipo' => 'estatico',
    ];

    /**
     * Obtener el concepto relacionado
     */
    public function concepto()
    {
        return $this->belongsTo(Concepto::class, 'concepto_id');
    }
}

