<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Pages\PaginaInicial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

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
            'password' => ['required', 'confirmed', 'min:8'],
            'endereco' => ['required', 'string', 'max:500'],
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

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
