<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Verificar si hay detalles
        $hasDetalles = false;
        $detalles = [];
        
        if (isset($this->detalles)) {
            $detalles = is_array($this->detalles) ? $this->detalles : (array) $this->detalles;
            $hasDetalles = !empty($detalles);
        }

        $baseData = [
            'id' => $this->id ?? null,
            'tenant' => $this->tenant ?? null,
            'phone' => $this->phone ?? null,
            'house' => $this->house ?? null,
            'year' => $this->year ?? null,
            'month' => $this->month ?? null,
            'particulars' => $this->particulars ?? null,
            'total' => $this->total ?? null,
            'comments' => $this->comments ?? null,
            'status' => $this->status ?? null,
            'fecha_emision' => $this->fecha_emision ?? null,
            'fecha_vencimiento' => $this->fecha_vencimiento ?? null,
            'medidor_image' => $this->medidor_image ?? null,
            
            // Información del residente (desde JOIN)
            'residente' => [
                'nombre' => $this->residente_nombre ?? null,
                'telefono' => $this->residente_telefono ?? null,
                'email' => $this->residente_email ?? null,
            ],
            
            // Información del departamento (desde JOIN)
            'departamento' => [
                'house_numero' => $this->departamento_numero ?? null,
                'nombre_edificio' => $this->departamento_nombre ?? null,
            ],
            
            // Información del schema (solo cuando residencia_id = 0)
            'schema' => $this->schema ?? null,
            'residencia_nombre' => $this->residencia_nombre ?? null,
        ];

        // Si hay detalles, mostrar solo los detalles (nueva estructura)
        if ($hasDetalles) {
            $baseData['detalles'] = InvoiceDetalleResource::collection(collect($detalles));
        } else {
            // Si no hay detalles, mantener los campos antiguos (estructura antigua)
            // Usar null coalescing para evitar errores si las columnas no existen
            $baseData['total_part'] = $this->total_part ?? null;
            $baseData['total_agua'] = $this->total_agua ?? null;
            $baseData['total_asen'] = $this->total_asen ?? null;
            $baseData['total_mant2'] = $this->total_mant2 ?? null;
            $baseData['total_mant3'] = $this->total_mant3 ?? null;
            $baseData['monto_extra'] = $this->monto_extra ?? null;
            $baseData['lectura_actual'] = $this->lectura_actual ?? null;
            $baseData['lectura_pasada'] = $this->lectura_pasada ?? null;
            $baseData['diferencia'] = $this->diferencia ?? null;
            $baseData['total_luzssgg'] = $this->total_luzssgg ?? null;
            $baseData['total_luzbci'] = $this->total_luzbci ?? null;
            $baseData['total_aguacomun'] = $this->total_aguacomun ?? null;
            $baseData['total_mant'] = $this->total_mant ?? null;
            $baseData['total_extraordinaria'] = $this->total_extraordinaria ?? null;
            $baseData['detalles'] = [];
        }

        return $baseData;
    }
}

