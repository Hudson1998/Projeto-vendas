@extends('layouts.base')

@section('title', 'Criar conta · HR Moda Feminina')

@section('content')
<div class="auth-card">
  <h1 class="auth-card__title">Criar conta</h1>
  <p class="auth-card__subtitle">Cadastre-se para comprar suas peças favoritas.</p>

  @if ($errors->any())
    <div class="form-status form-status--error">
      @foreach ($errors->all() as $error)
        <p>{{ $error }}</p>
      @endforeach
    </div>
  @endif

  <form method="POST" action="{{ route('register') }}">
    @csrf

    <div class="field">
      <label for="name">Nome</label>
      <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus>
    </div>

    <div class="field">
      <label for="email">E-mail</label>
      <input type="email" id="email" name="email" value="{{ old('email') }}" required>
    </div>

    <div class="field">
      <label for="endereco">Endereço de entrega</label>
      <input type="text" id="endereco" name="endereco" value="{{ old('endereco') }}" placeholder="Rua, número, bairro, cidade - UF" required>
    </div>

    <div class="field">
      <label for="password">Senha</label>
      <input type="password" id="password" name="password" required>
    </div>

    <div class="field">
      <label for="password_confirmation">Confirmar senha</label>
      <input type="password" id="password_confirmation" name="password_confirmation" required>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Cadastrar</button>
  </form>

  <p class="auth-card__footer">Já tem conta? <a href="{{ route('login') }}">Entrar</a></p>
</div>
@endsection
