<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceDetalle extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla
     */
    protected $table = 'invoice_detalles';

    /**
     * Los atributos que se pueden asignar masivamente
     */
    protected $fillable = [
        'invoice_id',
        'concepto_id',
        'monto',
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
        'monto' => 0.00,
    ];

    /**
     * Obtener el concepto relacionado
     */
    public function concepto()
    {
        return $this->belongsTo(Concepto::class, 'concepto_id');
    }
}

