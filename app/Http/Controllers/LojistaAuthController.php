<?php

namespace App\Http\Controllers;

use App\Models\LojistaProfile;
use App\Models\User;
use App\Rules\Cnpj;
use App\Rules\Cpf;
use App\Rules\InscricaoEstadual;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LojistaAuthController extends Controller
{
    public function showRegister(): View
    {
        return view('auth.register-lojista');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'telefone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', 'min:8'],

            'nome_fantasia' => ['required', 'string', 'max:255'],
            'tipo_pessoa' => ['required', Rule::in(['fisica', 'juridica'])],
            'cpf' => ['nullable', 'required_if:tipo_pessoa,fisica', new Cpf],
            'cnpj' => ['nullable', 'required_if:tipo_pessoa,juridica', new Cnpj],
            'razao_social' => ['nullable', 'required_if:tipo_pessoa,juridica', 'string', 'max:255'],
            'ie_isento' => ['sometimes', 'boolean'],
            'inscricao_estadual' => ['nullable', 'required_unless:ie_isento,1', 'string', new InscricaoEstadual],
            'logotipo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'descricao_loja' => ['required', 'string', 'max:2000'],

            'cep' => ['required', 'string', 'max:9'],
            'rua' => ['required', 'string', 'max:255'],
            'numero' => ['required', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:255'],
            'bairro' => ['required', 'string', 'max:255'],
            'cidade' => ['required', 'string', 'max:255'],
            'estado' => ['required', 'string', 'size:2'],

            'prazo_expedicao_dias_uteis' => ['required', 'integer', 'min:1', 'max:365'],
            'politica_troca_devolucao' => ['required', 'string', 'max:5000'],

            'documento_identidade' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'selfie_documento' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'contrato_social_mei' => ['nullable', 'required_if:tipo_pessoa,juridica', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'comprovante_endereco' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'lojista',
        ]);

        $logotipoPath = $request->file('logotipo')->store('lojistas/logos', 'public');

        $pastaDocumentos = "lojistas-kyc/{$user->id}";
        $docIdentidadePath = $request->file('documento_identidade')->store($pastaDocumentos, 'local');
        $selfiePath = $request->file('selfie_documento')->store($pastaDocumentos, 'local');
        $contratoSocialPath = $request->hasFile('contrato_social_mei')
            ? $request->file('contrato_social_mei')->store($pastaDocumentos, 'local')
            : null;
        $comprovanteEnderecoPath = $request->file('comprovante_endereco')->store($pastaDocumentos, 'local');

        $ieIsento = $request->boolean('ie_isento');

        LojistaProfile::create([
            'user_id' => $user->id,
            'telefone' => $data['telefone'],
            'nome_fantasia' => $data['nome_fantasia'],
            'tipo_pessoa' => $data['tipo_pessoa'],
            'cpf' => isset($data['cpf']) ? preg_replace('/\D/', '', $data['cpf']) : null,
            'cnpj' => isset($data['cnpj']) ? preg_replace('/\D/', '', $data['cnpj']) : null,
            'razao_social' => $data['razao_social'] ?? null,
            'inscricao_estadual' => $ieIsento ? null : preg_replace('/\D/', '', $data['inscricao_estadual']),
            'ie_isento' => $ieIsento,
            'logotipo' => $logotipoPath,
            'descricao_loja' => $data['descricao_loja'],
            'cep' => $data['cep'],
            'rua' => $data['rua'],
            'numero' => $data['numero'],
            'complemento' => $data['complemento'] ?? null,
            'bairro' => $data['bairro'],
            'cidade' => $data['cidade'],
            'estado' => strtoupper($data['estado']),
            'prazo_expedicao_dias_uteis' => $data['prazo_expedicao_dias_uteis'],
            'politica_troca_devolucao' => $data['politica_troca_devolucao'],
            'doc_identidade_path' => $docIdentidadePath,
            'selfie_documento_path' => $selfiePath,
            'contrato_social_mei_path' => $contratoSocialPath,
            'comprovante_endereco_path' => $comprovanteEnderecoPath,
        ]);

        Auth::login($user);

        return redirect()->route('home')->with('status', 'Cadastro de lojista realizado com sucesso! Bem-vindo(a), '.$user->name.'.');
    }
}
