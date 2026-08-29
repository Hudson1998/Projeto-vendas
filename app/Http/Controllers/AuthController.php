<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Pages\PaginaInicial;
use App\Support\ContaGoogle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $tentativa = (new PaginaInicial)->login($credentials['email'], $credentials['password'], $request->boolean('remember'));

        if ($tentativa->tentativasFalhas > 0) {
            return back()->withErrors([
                'email' => 'E-mail ou senha inválidos.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        // cada papel entra na propria casa. O lojista caia na vitrine, sem
        // sidebar e sem nenhum link visivel para /loja -- so chegava ao painel
        // quem digitasse a URL na mao.
        if (Auth::user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if (Auth::user()->loja) {
            return redirect()->route('loja.dashboard');
        }

        return redirect()->intended(route('home'));
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            // 10 caracteres e ao menos um simbolo. Escrito em regras soltas, e
            // nao com Password::min(10)->symbols(), porque a regra do Laravel
            // emite a propria mensagem, em ingles, e o projeto nao tem arquivo
            // de traducao: aqui as duas mensagens sao nossas.
            'password' => ['required', 'confirmed', 'min:10', 'regex:/[^\p{L}\p{N}]/u'],
            'endereco' => ['required', 'string', 'max:500'],
        ], [
            'name.required' => 'Preencha o seu nome.',
            'email.required' => 'Preencha o seu e-mail.',
            'email.email' => 'Digite um e-mail válido, como nome@provedor.com.',
            'email.unique' => 'Já existe uma conta com esse e-mail. Tente entrar.',
            'password.required' => 'Escolha uma senha.',
            'password.confirmed' => 'As duas senhas não são iguais.',
            'password.min' => 'A senha precisa ter no mínimo 10 caracteres.',
            'password.regex' => 'A senha precisa ter pelo menos um caractere especial, como ! @ # $ % & *.',
            'endereco.required' => 'Preencha o endereço completo: CEP, rua, número, bairro, cidade e UF.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'endereco' => $data['endereco'],
            'role' => 'cliente',
        ]);

        Auth::login($user);

        return redirect()->route('home')->with('status', 'Cadastro realizado com sucesso! Bem-vinda(o), '.$user->name.'.');
    }

    // ===================== Entrada pela conta Google =====================

    /**
     * Manda o navegador para o Google, guardando o state na sessao.
     *
     * O state e conferido na volta: e o que separa uma callback vinda deste
     * navegador de uma URL que alguem colou.
     */
    public function redirectGoogle(Request $request): RedirectResponse
    {
        if (! ContaGoogle::configurado()) {
            return redirect()->route('register')
                ->withErrors(['google' => 'A entrada pelo Google ainda não está configurada nesta loja.']);
        }

        $state = Str::random(40);
        $request->session()->put(ContaGoogle::CHAVE_STATE, $state);

        return redirect()->away(ContaGoogle::urlDeAutorizacao($state));
    }

    /**
     * Volta do Google: cria a conta na primeira vez, entra nas seguintes.
     */
    public function callbackGoogle(Request $request): RedirectResponse
    {
        if (! ContaGoogle::configurado()) {
            return redirect()->route('register');
        }

        // o state e de uso unico: fica invalido assim que sai da sessao
        $esperado = $request->session()->pull(ContaGoogle::CHAVE_STATE);

        if (blank($esperado) || ! hash_equals($esperado, (string) $request->query('state'))) {
            return redirect()->route('register')
                ->withErrors(['google' => 'A resposta do Google não confere com este navegador. Tente de novo.']);
        }

        if (filled($request->query('error')) || blank($request->query('code'))) {
            return redirect()->route('register')
                ->withErrors(['google' => 'Você cancelou a entrada pelo Google.']);
        }

        try {
            $perfil = ContaGoogle::perfilDoCodigo((string) $request->query('code'));
        } catch (Throwable $e) {
            Log::warning('google.callback_falhou', ['erro' => $e->getMessage()]);

            return redirect()->route('register')
                ->withErrors(['google' => 'Não foi possível concluir a entrada pelo Google. Tente de novo.']);
        }

        if (! $perfil['email_verificado']) {
            return redirect()->route('register')
                ->withErrors(['google' => 'Essa conta Google ainda não tem o e-mail verificado.']);
        }

        $novo = false;
        $user = User::where('google_id', $perfil['google_id'])->first();

        if (! $user) {
            // e-mail ja cadastrado com senha: liga as duas contas em vez de
            // recusar, senao a pessoa fica com dois cadastros do mesmo e-mail
            $user = User::where('email', $perfil['email'])->first();

            if ($user) {
                $user->update(['google_id' => $perfil['google_id']]);
            } else {
                $novo = true;
                $user = User::create([
                    'name' => $perfil['nome'],
                    'email' => $perfil['email'],
                    'google_id' => $perfil['google_id'],
                    'role' => 'cliente',
                    // o Google nao da endereco; e o que falta para comprar,
                    // entao a conta nasce apontando para o perfil
                    'endereco' => null,
                    'email_verified_at' => now(),
                ]);
            }
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        if ($novo || blank($user->endereco)) {
            return redirect()->route('profile.edit')
                ->with('status', 'Bem-vinda(o), '.$user->name.'! Falta só o endereço de entrega para você comprar.');
        }

        return redirect()->route('home')->with('status', 'Bem-vinda(o) de volta, '.$user->name.'.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
