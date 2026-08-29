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

  {{-- o resumo diz o que fazer; o detalhe de cada campo fica no proprio campo,
       onde a pessoa esta olhando na hora de corrigir --}}
  @if ($errors->any())
    <div class="form-status form-status--error" role="alert">
      <p><strong>Preencha o formulário corretamente para continuar.</strong></p>
      <p>Os campos marcados em vermelho abaixo precisam da sua atenção.</p>
      @error('google')<p>{{ $message }}</p>@enderror
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

    <div class="auth-separador"><span>ou preencha seus dados</span></div>
  @endif

  {{-- o mesmo aviso do servidor, preenchido pelo validador do navegador
       quando o envio e barrado antes de sair da pagina --}}
  <div class="form-status form-status--error" id="resumo-erros" role="alert" hidden>
    <p><strong>Preencha o formulário corretamente para continuar.</strong></p>
    <p data-contagem></p>
  </div>

  <form method="POST" action="{{ route('register') }}" id="form-cadastro" novalidate>
    @csrf

    <div class="field">
      <label for="name">Nome</label>
      <input type="text" id="name" name="name" value="{{ old('name') }}"
             data-obrigatorio="Preencha o seu nome."
             class="@error('name') is-invalid @enderror" autofocus>
      <span class="field__erro" @if(! $errors->has('name')) hidden @endif>{{ $errors->first('name') }}</span>
    </div>

    <div class="field">
      <label for="email">E-mail</label>
      <input type="email" id="email" name="email" value="{{ old('email') }}"
             data-obrigatorio="Preencha o seu e-mail."
             class="@error('email') is-invalid @enderror">
      <span class="field__erro" @if(! $errors->has('email')) hidden @endif>{{ $errors->first('email') }}</span>
    </div>

    @include('partials.address-fields', ['obrigatorio' => true])

    <div class="field">
      <label for="password">Senha</label>
      <input type="password" id="password" name="password"
             data-obrigatorio="Escolha uma senha."
             class="@error('password') is-invalid @enderror">
      <span class="field__hint">No mínimo 10 caracteres, com pelo menos um caractere especial (!&#64;#$%&amp;*...).</span>
      <span class="senha-forca" id="senha-forca" hidden>
        <span class="senha-forca__item" data-regra="tamanho">10 caracteres</span>
        <span class="senha-forca__item" data-regra="simbolo">1 caractere especial</span>
      </span>
      <span class="field__erro" @if(! $errors->has('password')) hidden @endif>{{ $errors->first('password') }}</span>
    </div>

    <div class="field">
      <label for="password_confirmation">Confirmar senha</label>
      <input type="password" id="password_confirmation" name="password_confirmation"
             data-obrigatorio="Repita a senha para confirmar.">
      <span class="field__erro" id="erro-confirmacao" hidden></span>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Cadastrar</button>
  </form>

  <p class="auth-card__footer">Já tem conta? <a href="{{ route('login') }}">Entrar</a></p>
</div>

@push('scripts')
<script src="{{ asset_v('js/cadastro-validacao.js') }}"></script>
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
