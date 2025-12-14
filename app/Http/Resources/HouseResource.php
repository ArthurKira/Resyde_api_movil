<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HouseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'house_number' => $this->house_number,
            'features' => $this->features,
            'rent' => $this->rent,
            'status' => $this->status,
            'idresidencia' => $this->idresidencia,
            'recibo_fisico' => $this->recibo_fisico,
        ];
    }
}

