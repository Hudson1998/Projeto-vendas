@extends('layouts.base')

@section('title', 'Entrar · HR Moda Online')

@section('content')
<div class="auth-card">
  <h1 class="auth-card__title">Entrar</h1>
  <p class="auth-card__subtitle">Acesse sua conta para continuar.</p>

  @if ($errors->any())
    <div class="form-status form-status--error">
      @foreach ($errors->all() as $error)
        <p>{{ $error }}</p>
      @endforeach
    </div>
  @endif

  @if (App\Support\ContaGoogle::configurado())
    <a href="{{ route('google.redirect') }}" class="btn-google">
      <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
        <path fill="#4285F4" d="M45.1 24.5c0-1.6-.1-3.2-.4-4.7H24v8.9h11.8a10 10 0 0 1-4.4 6.6v5.5h7.1c4.2-3.8 6.6-9.5 6.6-16.3z"></path>
        <path fill="#34A853" d="M24 46c6 0 11-2 14.6-5.2l-7.1-5.5a13.6 13.6 0 0 1-20.3-7.1H3.9v5.7A22 22 0 0 0 24 46z"></path>
        <path fill="#FBBC05" d="M11.2 28.2a13.2 13.2 0 0 1 0-8.4v-5.7H3.9a22 22 0 0 0 0 19.8l7.3-5.7z"></path>
        <path fill="#EA4335" d="M24 10.3c3.3 0 6.2 1.1 8.5 3.3l6.3-6.3A21 21 0 0 0 24 2 22 22 0 0 0 3.9 14.1l7.3 5.7A13.1 13.1 0 0 1 24 10.3z"></path>
      </svg>
      Continuar com o Google
    </a>

    <div class="auth-separador"><span>ou entre com e-mail</span></div>
  @endif

  <form method="POST" action="{{ route('login') }}">
    @csrf

    <div class="field">
      <label for="email">E-mail</label>
      <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
    </div>

    <div class="field">
      <label for="password">Senha</label>
      <input type="password" id="password" name="password" required>
    </div>

    <div class="field field--inline">
      <label class="checkbox">
        <input type="checkbox" name="remember">
        <span>Lembrar de mim</span>
      </label>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Entrar</button>
  </form>

  <p class="auth-card__footer">Ainda não tem conta? <a href="{{ route('register') }}">Cadastre-se</a></p>
</div>
@endsection
