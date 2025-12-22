<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Personal extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla
     */
    protected $table = 'personal';

    /**
     * Clave primaria personalizada
     */
    protected $primaryKey = 'id_personal';

    /**
     * Indica si el modelo debe usar timestamps automáticos
     */
    public $timestamps = false;

    /**
     * Los atributos que se pueden asignar masivamente
     */
    protected $fillable = [
        'nombres',
        'apellidos',
        'dni_ce',
        'fecha_nacimiento',
        'estado_civil',
        'tiene_hijos',
        'fecha_ingreso_uclub',
        'tipo_contrato',
        'fecha_ingreso_planilla',
        'estado',
        'fecha_cese',
        'correo_electronico',
        'telefono',
        'direccion',
        'distrito',
        'referencia_direccion',
        'fotocheck_entregado',
        'fecha_creacion',
        'usuario_creacion',
        'fecha_modificacion',
        'usuario_modificacion',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos
     */
    protected $casts = [
        'tiene_hijos' => 'boolean',
        'fotocheck_entregado' => 'boolean',
        'fecha_nacimiento' => 'date',
        'fecha_ingreso_uclub' => 'date',
        'fecha_ingreso_planilla' => 'date',
        'fecha_cese' => 'date',
        'fecha_creacion' => 'datetime',
        'fecha_modificacion' => 'datetime',
    ];

    /**
     * Obtener las asignaciones de residencias del personal
     */
    public function personalResidencias(): HasMany
    {
        return $this->hasMany(PersonalResidencia::class, 'id_personal', 'id_personal');
    }

    /**
     * Obtener los usuarios asociados a este personal
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'personal_id', 'id_personal');
    }
}
