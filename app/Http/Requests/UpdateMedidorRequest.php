<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedidorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'imagen' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'], // 5MB máximo
            'lectura_actual' => ['required', 'string', 'max:45'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'imagen.required' => 'La imagen del medidor es obligatoria.',
            'imagen.image' => 'El archivo debe ser una imagen válida.',
            'imagen.mimes' => 'La imagen debe ser de tipo: jpeg, jpg, png o webp.',
            'imagen.max' => 'La imagen no debe pesar más de 5MB.',
            'lectura_actual.required' => 'La lectura actual del medidor es obligatoria.',
            'lectura_actual.string' => 'La lectura actual debe ser un texto.',
            'lectura_actual.max' => 'La lectura actual no debe exceder 45 caracteres.',
        ];
    }
}

