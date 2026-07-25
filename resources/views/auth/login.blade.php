@extends('layouts.base')

@section('title', 'Entrar · HR Moda Feminina')

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
