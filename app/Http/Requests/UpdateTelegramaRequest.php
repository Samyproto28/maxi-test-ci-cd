<?php

namespace App\Http\Requests;

use App\Services\TelegramaValidationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'mesa_id' => 'required|exists:mesas,id',
            'lista_id' => [
                'required',
                'exists:listas,id',
                Rule::unique('telegramas')
                    ->where('mesa_id', $this->mesa_id)
                    ->ignore($this->route('telegrama'))
            ],
            'votos_diputados' => 'required|integer|min:0',
            'votos_senadores' => 'required|integer|min:0',
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
            $validationService = app(TelegramaValidationService::class);

            try {
                // Validar que no hay votos negativos
                $validationService->validarVotosNoNegativos($this->all());

                // Validar que suma <= electores de la mesa (excluyendo el telegrama actual)
                $validationService->validarSumaVotosNoExcedeElectores(
                    $this->mesa_id,
                    $this->all(),
                    $this->route('telegrama')
                );
            } catch (\InvalidArgumentException $e) {
                $validator->errors()->add('votos', $e->getMessage());
            }
        });
    }
}
