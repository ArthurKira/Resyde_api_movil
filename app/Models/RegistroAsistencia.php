<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroAsistencia extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla
     */
    protected $table = 'registro_asistencia';

    /**
     * Clave primaria personalizada
     */
    protected $primaryKey = 'id_registro';

    /**
     * Indica si el modelo debe usar timestamps automáticos
     */
    public $timestamps = false;

    /**
     * Los atributos que se pueden asignar masivamente
     */
    protected $fillable = [
        'id_personal_residencia',
        'fecha_entrada',
        'fecha_salida',
        'hora_entrada',
        'hora_salida',
        'latitud_entrada',
        'longitud_entrada',
        'latitud_salida',
        'longitud_salida',
        'foto_entrada',
        'foto_salida',
        'estado',
        'observaciones',
        'fecha_creacion',
        'usuario_creacion',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos
     */
    protected $casts = [
        'fecha_entrada' => 'date',
        'fecha_salida' => 'date',
        'hora_entrada' => 'datetime',
        'hora_salida' => 'datetime',
        'latitud_entrada' => 'decimal:8',
        'longitud_entrada' => 'decimal:8',
        'latitud_salida' => 'decimal:8',
        'longitud_salida' => 'decimal:8',
        'fecha_creacion' => 'datetime',
    ];

    /**
     * Obtener la asignación de personal-residencia
     */
    public function personalResidencia(): BelongsTo
    {
        return $this->belongsTo(PersonalResidencia::class, 'id_personal_residencia', 'id');
    }
}
