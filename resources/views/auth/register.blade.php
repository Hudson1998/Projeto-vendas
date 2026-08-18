@extends('layouts.base')

@section('title', 'Criar conta · HR Moda Online')

@section('content')
@php $jaEscolheuCliente = $errors->any() || old('name'); @endphp
<div class="modal-overlay @if(! $jaEscolheuCliente) is-open @endif" id="tipo-cadastro-modal" aria-hidden="{{ $jaEscolheuCliente ? 'true' : 'false' }}">
  <div class="modal-box">
    <h2 class="modal-box__title">Como você quer se cadastrar?</h2>
    <p class="modal-box__text">Escolha uma opção para continuar.</p>
    <div class="modal-box__actions">
      <button type="button" class="btn btn-primary" id="btn-sou-cliente">Sou Cliente</button>
      <a href="{{ route('register.lojista') }}" class="btn btn-outline">Sou Lojista</a>
    </div>
  </div>
</div>

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

    @include('partials.address-fields', ['obrigatorio' => true])

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

@push('scripts')
<script>
  (function () {
    var modal = document.getElementById('tipo-cadastro-modal');
    var btnCliente = document.getElementById('btn-sou-cliente');
    if (btnCliente) {
      btnCliente.addEventListener('click', function () {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
      });
    }
  })();
</script>
@endpush
@endsection
