<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceDetalleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Manejar tanto objetos como arrays
        $resource = is_array($this->resource) ? (object) $this->resource : $this->resource;
        
        return [
            'id' => $resource->id ?? null,
            'invoice_id' => $resource->invoice_id ?? null,
            'concepto_id' => $resource->concepto_id ?? null,
            'monto' => $resource->monto ?? null,
            
            // Información del concepto (desde JOIN)
            'concepto' => [
                'codigo' => $resource->concepto_codigo ?? null,
                'nombre' => $resource->concepto_nombre ?? null,
                'descripcion' => $resource->concepto_descripcion ?? null,
                'tipo' => $resource->concepto_tipo ?? null,
            ],
        ];
    }
}

