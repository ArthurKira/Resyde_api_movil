<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vacacion extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla
     */
    protected $table = 'vacaciones';

    /**
     * Clave primaria personalizada
     */
    protected $primaryKey = 'id_vacacion';

    /**
     * Indica si el modelo debe usar timestamps automáticos
     */
    public $timestamps = false;

    /**
     * Los atributos que se pueden asignar masivamente
     */
    protected $fillable = [
        'id_personal',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'dias',
        'observaciones',
        'fecha_creacion',
        'usuario_creacion',
        'fecha_modificacion',
        'usuario_modificacion',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos
     */
    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_creacion' => 'datetime',
        'fecha_modificacion' => 'datetime',
    ];

    /**
     * Obtener el personal asociado
     */
    public function personal(): BelongsTo
    {
        return $this->belongsTo(Personal::class, 'id_personal', 'id_personal');
    }
}
