<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResidenciaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_residencia' => $this->id_residencia,
            'nombre' => $this->nombre,
            'schema_relacionado' => $this->schema_relacionado,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'fecha_creacion' => $this->fecha_creacion?->format('Y-m-d H:i:s'),
            'usuario_creacion' => $this->usuario_creacion,
            'fecha_modificacion' => $this->fecha_modificacion?->format('Y-m-d H:i:s'),
            'usuario_modificacion' => $this->usuario_modificacion,
            'users_count' => $this->whenCounted('users'),
            'users' => $this->whenLoaded('users', function () {
                return UserResource::collection($this->users);
            }),
        ];
    }
}

