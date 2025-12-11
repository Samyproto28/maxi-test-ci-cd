<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProvinciaRequest extends FormRequest
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
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('provincias', 'nombre')->ignore($this->route('provincia'))
            ],
            'codigo' => [
                'required',
                'string',
                'max:10',
                'regex:/^[A-Z0-9]+$/',
                'uppercase',
                Rule::unique('provincias', 'codigo')->ignore($this->route('provincia'))
            ],
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
            'nombre.required' => 'El nombre de la provincia es obligatorio',
            'nombre.unique' => 'Ya existe una provincia con ese nombre',
            'nombre.max' => 'El nombre no puede exceder 100 caracteres',
            'codigo.required' => 'El código de provincia es obligatorio',
            'codigo.unique' => 'Ya existe una provincia con ese código',
            'codigo.max' => 'El código no puede exceder 10 caracteres',
            'codigo.regex' => 'El código solo puede contener letras mayúsculas y números',
        ];
    }
}
