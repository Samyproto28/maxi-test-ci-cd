<?php

namespace App\Http\Requests;

use App\Models\Telegrama;
use App\Services\TelegramaValidationService;
use Illuminate\Foundation\Http\FormRequest;

class StoreTelegramaRequest extends FormRequest
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
            'mesa_id' => [
                'required',
                'exists:mesas,id',
                function ($attribute, $value, $fail) {
                    if (Telegrama::where('mesa_id', $value)->exists()) {
                        $fail('Ya existe un telegrama cargado para esta mesa.');
                    }
                }
            ],
            'votos' => 'required|array|min:1',
            'votos.*.lista_id' => 'required|exists:listas,id',
            'votos.*.votos_diputados' => 'required|integer|min:0',
            'votos.*.votos_senadores' => 'required|integer|min:0',
            'blancos' => 'required|integer|min:0',
            'nulos' => 'required|integer|min:0',
            'recurridos' => 'required|integer|min:0',
            'usuario' => 'required|string|max:100',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $validationService = app(TelegramaValidationService::class);

            try {
                // Validar que suma de votos <= electores de la mesa
                $validationService->validarSumaVotosNoExcedeElectores(
                    $this->mesa_id,
                    $this->all()
                );
            } catch (\InvalidArgumentException $e) {
                $validator->errors()->add('votos', $e->getMessage());
            }
        });
    }

    /**
     * Custom error messages
     */
    public function messages(): array
    {
        return [
            'mesa_id.required' => 'La mesa es requerida.',
            'mesa_id.exists' => 'La mesa seleccionada no existe.',
            'votos.required' => 'Debe incluir al menos un voto por lista.',
            'votos.array' => 'Los votos deben ser un array.',
            'votos.min' => 'Debe incluir al menos un voto por lista.',
            'votos.*.lista_id.required' => 'El ID de lista es requerido.',
            'votos.*.lista_id.exists' => 'Una de las listas seleccionadas no existe.',
            'votos.*.votos_diputados.required' => 'Los votos de diputados son requeridos.',
            'votos.*.votos_diputados.min' => 'Los votos de diputados no pueden ser negativos.',
            'votos.*.votos_senadores.required' => 'Los votos de senadores son requeridos.',
            'votos.*.votos_senadores.min' => 'Los votos de senadores no pueden ser negativos.',
            'blancos.required' => 'Los votos en blanco son requeridos.',
            'blancos.min' => 'Los votos en blanco no pueden ser negativos.',
            'nulos.required' => 'Los votos nulos son requeridos.',
            'nulos.min' => 'Los votos nulos no pueden ser negativos.',
            'recurridos.required' => 'Los votos recurridos son requeridos.',
            'recurridos.min' => 'Los votos recurridos no pueden ser negativos.',
            'usuario.required' => 'El usuario es requerido.',
        ];
    }
}
