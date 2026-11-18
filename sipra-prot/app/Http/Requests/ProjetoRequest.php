<?php

namespace App\Http\Requests;

use App\Enums\StatusProjeto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjetoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $empresa = $this->user()->empresa_id;

        return [
            'nome' => ['required', 'string', 'max:255'],
            'cliente_id' => ['required', Rule::exists('clientes', 'id')
                ->where('empresa_id', $empresa)->whereNull('deleted_at')],
            'responsavel_id' => ['nullable', Rule::exists('users', 'id')->where('empresa_id', $empresa)],
            'status' => ['required', Rule::enum(StatusProjeto::class)],
            'prazo' => ['nullable', 'date'],
            'briefing' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome do projeto.',
            'cliente_id.required' => 'Escolha o cliente.',
            'cliente_id.exists' => 'Cliente inválido.',
            'responsavel_id.exists' => 'Responsável inválido.',
            'status.required' => 'Escolha o status.',
            'prazo.date' => 'Informe uma data válida.',
        ];
    }
}