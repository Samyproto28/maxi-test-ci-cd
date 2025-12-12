<?php

namespace App\Http\Requests;

use App\Services\TelegramaValidationService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTelegramaRequest extends FormRequest
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
            'votos' => 'sometimes|array|min:1',
            'votos.*.lista_id' => 'required_with:votos|exists:listas,id',
            'votos.*.votos_diputados' => 'required_with:votos|integer|min:0',
            'votos.*.votos_senadores' => 'required_with:votos|integer|min:0',
            'blancos' => 'sometimes|integer|min:0',
            'nulos' => 'sometimes|integer|min:0',
            'recurridos' => 'sometimes|integer|min:0',
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
            $telegrama = $this->route('telegrama');

            try {
                // Validar que suma <= electores de la mesa (excluyendo el telegrama actual)
                $validationService->validarSumaVotosNoExcedeElectores(
                    $telegrama->mesa_id,
                    $this->all(),
                    $telegrama->id
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
            'votos.array' => 'Los votos deben ser un array.',
            'votos.min' => 'Debe incluir al menos un voto por lista.',
            'votos.*.lista_id.required_with' => 'El ID de lista es requerido.',
            'votos.*.lista_id.exists' => 'Una de las listas seleccionadas no existe.',
            'votos.*.votos_diputados.min' => 'Los votos de diputados no pueden ser negativos.',
            'votos.*.votos_senadores.min' => 'Los votos de senadores no pueden ser negativos.',
            'blancos.min' => 'Los votos en blanco no pueden ser negativos.',
            'nulos.min' => 'Los votos nulos no pueden ser negativos.',
            'recurridos.min' => 'Los votos recurridos no pueden ser negativos.',
            'usuario.required' => 'El usuario es requerido.',
        ];
    }
}
