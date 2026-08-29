@extends('layouts.base')

@section('title', 'Criar conta · HR Moda Online')

@section('content')
{{-- O modal e a porta de entrada: escolher cliente ou lojista. Nasce fechado e
     quem o abre e o JS, uma vez so por sessao do navegador -- ver o script no
     fim do arquivo. Antes ele dependia de $errors->any(), e reaparecia a cada
     tentativa que voltava com erro. --}}
<div class="modal-overlay" id="tipo-cadastro-modal" aria-hidden="true">
  <div class="modal-box">
    <h2 class="modal-box__title">Como você quer se cadastrar?</h2>
    <p class="modal-box__text">Escolha uma opção para continuar.</p>
    <div class="modal-box__actions">
      <button type="button" class="btn btn-primary" id="btn-sou-cliente">Sou Cliente</button>
      <a href="{{ route('register.lojista') }}" class="btn btn-outline" id="btn-sou-lojista">Sou Lojista</a>
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

  @include('partials.botao-google')

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
  registerPageInit(function () {
    var modal = document.getElementById('tipo-cadastro-modal');
    if (!modal) return;

    // Uma vez por sessao do navegador. sessionStorage e nao uma variavel porque
    // a escolha tem de sobreviver a um F5 e a uma volta do servidor com erro --
    // era ai que o modal reaparecia, como se a pagina tivesse recomecado.
    var CHAVE = 'cadastro-tipo-escolhido';
    var jaEscolheu = false;

    try {
      jaEscolheu = sessionStorage.getItem(CHAVE) === '1';
    } catch (e) {
      // navegador com armazenamento bloqueado: mostra o modal, sem quebrar
    }

    function fechar() {
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('modal-open');
      try { sessionStorage.setItem(CHAVE, '1'); } catch (e) { /* idem */ }
    }

    if (!jaEscolheu) {
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('modal-open');
    }

    var btnCliente = document.getElementById('btn-sou-cliente');
    if (btnCliente) btnCliente.addEventListener('click', fechar);

    // ir para o cadastro de lojista tambem conta como escolha feita
    var btnLojista = document.getElementById('btn-sou-lojista');
    if (btnLojista) btnLojista.addEventListener('click', fechar);
  });
</script>
@endpush
@endsection
