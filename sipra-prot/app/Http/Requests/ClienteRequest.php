<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome'      => ['required', 'string', 'max:255'],
            'tipo'      => ['required', 'in:PF,PJ'],
            'documento' => ['nullable', 'string', 'max:20'],
            'telefone'  => ['nullable', 'string', 'max:20'],
            'email'     => ['nullable', 'email', 'max:255'],
            'cidade'    => ['nullable', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome do cliente.',
            'tipo.required' => 'Escolha o tipo: pessoa física ou jurídica.',
            'tipo.in'       => 'Tipo inválido.',
            'email.email'   => 'Informe um e-mail válido.',
            '*.max'         => 'Texto longo demais para este campo.',
        ];
    }
}