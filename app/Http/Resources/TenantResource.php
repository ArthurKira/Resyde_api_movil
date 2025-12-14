<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
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
            'fullname' => $this->fullname,
            'gender' => $this->gender,
            'national_id' => $this->national_id,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'registration_date' => $this->registration_date,
            'house' => $this->house,
            'status' => $this->status,
            'exit_date' => $this->exit_date,
            'agreement_document' => $this->agreement_document,
            'estacionamiento' => $this->estacionamiento,
            'estacionamiento2' => $this->estacionamiento2,
            'estacionamiento3' => $this->estacionamiento3,
            'correo_envio' => $this->correo_envio,
            'email_cc' => $this->email_cc,
            'email_cc2' => $this->email_cc2,
            'flag_reserva' => $this->flag_reserva,
        ];
    }
}

