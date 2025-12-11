<?php

namespace App\Http\Requests;

use App\Models\Lista;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreListaRequest extends FormRequest
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
                Rule::unique('listas')
                    ->where('provincia_id', $this->provincia_id)
                    ->where('cargo', $this->cargo)
            ],
            'alianza' => 'nullable|string|max:100',
            'provincia_id' => 'required|exists:provincias,id',
            'cargo' => ['required', Rule::in(Lista::CARGOS)],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cargo.in' => 'El cargo debe ser DIPUTADOS o SENADORES',
            'provincia_id.exists' => 'La provincia especificada no existe',
        ];
    }
}
