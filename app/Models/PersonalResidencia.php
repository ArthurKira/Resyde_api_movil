<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PersonalResidencia extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla
     */
    protected $table = 'personal_residencia';

    /**
     * Clave primaria
     */
    protected $primaryKey = 'id';

    /**
     * Indica si el modelo debe usar timestamps automáticos
     */
    public $timestamps = true;

    /**
     * Los atributos que se pueden asignar masivamente
     */
    protected $fillable = [
        'id_personal',
        'id_residencia',
        'cargo',
        'fecha_asignacion',
        'fecha_fin',
        'activo',
        'usuario_creacion',
        'usuario_modificacion',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos
     */
    protected $casts = [
        'activo' => 'boolean',
        'fecha_asignacion' => 'date',
        'fecha_fin' => 'date',
    ];

    /**
     * Obtener el personal asociado
     */
    public function personal(): BelongsTo
    {
        return $this->belongsTo(Personal::class, 'id_personal', 'id_personal');
    }

    /**
     * Obtener la residencia asociada
     */
    public function residencia(): BelongsTo
    {
        return $this->belongsTo(Residencia::class, 'id_residencia', 'id_residencia');
    }

    /**
     * Obtener los registros de asistencia
     */
    public function registrosAsistencia(): HasMany
    {
        return $this->hasMany(RegistroAsistencia::class, 'id_personal_residencia', 'id');
    }

    /**
     * Obtener las asignaciones recurrentes
     */
    public function asignacionesRecurrentes(): HasMany
    {
        return $this->hasMany(AsignacionRecurrente::class, 'id_personal_residencia', 'id');
    }
}
