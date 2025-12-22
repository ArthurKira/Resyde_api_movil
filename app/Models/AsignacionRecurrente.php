<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsignacionRecurrente extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla
     */
    protected $table = 'asignacion_recurrente';

    /**
     * Clave primaria personalizada
     */
    protected $primaryKey = 'id_asignacion_recurrente';

    /**
     * Indica si el modelo debe usar timestamps automáticos
     */
    public $timestamps = false;

    /**
     * Los atributos que se pueden asignar masivamente
     */
    protected $fillable = [
        'id_personal_residencia',
        'dias_semana',
        'hora_entrada',
        'hora_salida',
        'fecha_inicio',
        'fecha_fin',
        'activa',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos
     */
    protected $casts = [
        'hora_entrada' => 'datetime',
        'hora_salida' => 'datetime',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activa' => 'boolean',
    ];

    /**
     * Obtener la asignación de personal-residencia
     */
    public function personalResidencia(): BelongsTo
    {
        return $this->belongsTo(PersonalResidencia::class, 'id_personal_residencia', 'id');
    }
}
