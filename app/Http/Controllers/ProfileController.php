<?php

namespace App\Http\Controllers;

use App\Pages\PaginaInicial;
use App\Support\ImagemDePerfil;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = (new PaginaInicial)->perfil($request->user()->id);

        return view('profile.edit', ['user' => $user]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'endereco' => ['required', 'string', 'max:500'],
            'foto' => ImagemDePerfil::regras(),
            'remover_foto' => ['nullable', 'boolean'],
        ]);

        // a foto nao vem em $data->update porque o campo do banco guarda o
        // caminho, nao o arquivo enviado
        unset($data['foto'], $data['remover_foto']);

        if ($request->hasFile('foto')) {
            $data['foto'] = ImagemDePerfil::guardar($request->file('foto'), 'perfil_', $user->foto);
        } elseif ($request->boolean('remover_foto')) {
            ImagemDePerfil::apagar($user->foto);
            $data['foto'] = null;
        }

        $user->update($data);

        return back()->with('status', 'Dados atualizados com sucesso!');
    }
}
