<?php

namespace App\Http\Requests;

use App\Models\Candidato;
use App\Models\Lista;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCandidatoRequest extends FormRequest
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
        $candidatoId = $this->route('candidato');

        return [
            'nombre' => 'required|string|max:150',
            'lista_id' => 'required|exists:listas,id',
            'provincia_id' => 'required|exists:provincias,id',
            'cargo' => ['required', Rule::in(Candidato::CARGOS)],
            'orden' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('candidatos')
                    ->where('lista_id', $this->input('lista_id'))
                    ->ignore($candidatoId)
            ],
            'observaciones' => 'nullable|string',
        ];
    }

    /**
     * Perform additional validation after basic rules pass.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('lista_id')) {
                $lista = Lista::find($this->input('lista_id'));

                if ($lista && $this->input('cargo') !== $lista->cargo) {
                    $validator->errors()->add(
                        'cargo',
                        'El cargo del candidato debe coincidir con el cargo de la lista seleccionada.'
                    );
                }
            }
        });
    }
}
