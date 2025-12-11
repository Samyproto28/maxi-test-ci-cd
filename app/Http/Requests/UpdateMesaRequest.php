<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMesaRequest extends FormRequest
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
            'id_mesa' => [
                'required',
                'string',
                'max:20',
                Rule::unique('mesas', 'id_mesa')->ignore($this->route('mesa'))
            ],
            'provincia_id' => 'required|exists:provincias,id',
            'circuito' => 'nullable|string|max:50',
            'establecimiento' => 'nullable|string|max:200',
            'electores' => 'required|integer|min:1',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id_mesa.required' => 'El identificador de mesa es obligatorio',
            'id_mesa.unique' => 'Ya existe una mesa con ese identificador',
            'id_mesa.max' => 'El identificador de mesa no puede exceder 20 caracteres',
            'provincia_id.required' => 'La provincia es obligatoria',
            'provincia_id.exists' => 'La provincia seleccionada no existe',
            'circuito.max' => 'El circuito no puede exceder 50 caracteres',
            'establecimiento.max' => 'El establecimiento no puede exceder 200 caracteres',
            'electores.required' => 'La cantidad de electores es obligatoria',
            'electores.integer' => 'La cantidad de electores debe ser un número entero',
            'electores.min' => 'La cantidad de electores debe ser al menos 1',
        ];
    }
}
