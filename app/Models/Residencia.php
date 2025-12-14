<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Residencia extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla
     */
    protected $table = 'residencias';

    /**
     * Clave primaria personalizada
     */
    protected $primaryKey = 'id_residencia';

    /**
     * Indica si el modelo debe usar timestamps automáticos
     */
    public $timestamps = false;

    /**
     * Los atributos que se pueden asignar masivamente
     */
    protected $fillable = [
        'nombre',
        'schema_relacionado',
        'telefono',
        'email',
        'fecha_creacion',
        'usuario_creacion',
        'fecha_modificacion',
        'usuario_modificacion',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos
     */
    protected $casts = [
        'fecha_creacion' => 'datetime',
        'fecha_modificacion' => 'datetime',
    ];

    /**
     * Obtener los usuarios de la residencia
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'residencia_id', 'id_residencia');
    }
}

