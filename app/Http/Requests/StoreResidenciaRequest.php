<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreResidenciaRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:255'],
            'schema_relacionado' => ['nullable', 'string'],
            'telefono' => ['nullable', 'string', 'max:12'],
            'email' => ['nullable', 'email', 'max:255'],
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
            'nombre.required' => 'El nombre de la residencia es obligatorio.',
            'nombre.max' => 'El nombre no puede exceder 255 caracteres.',
            'telefono.max' => 'El teléfono no puede exceder 12 caracteres.',
            'email.email' => 'El email debe ser una dirección válida.',
            'email.max' => 'El email no puede exceder 255 caracteres.',
        ];
    }
}

