@extends('layouts.app')

@section('title', 'Configurações · HR Moda Online')

@section('content')
<section class="wrap page-section">
  <div class="page-header settings-anim" style="animation-delay: .02s">
    <h1 class="page-header__title">Configurações</h1>
  </div>
  <p class="settings-subtitle settings-anim" style="animation-delay: .05s">
    Atualize sua foto, seus dados pessoais e o endereço usado nas suas entregas.
  </p>

  <nav class="settings-quicknav settings-anim" style="animation-delay: .08s">
    <a href="#perfil">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21v-2a5 5 0 0 1 5-5h8a5 5 0 0 1 5 5v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
      Perfil
    </a>
    <a href="#endereco">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>
      Endereço
    </a>
    <a href="#conta">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"></path></svg>
      Conta
    </a>
  </nav>

  @if (session('status'))
    <div class="form-status">{{ session('status') }}</div>
  @endif

  @if ($errors->any())
    <div class="form-status form-status--error">
      @foreach ($errors->all() as $error)
        <p>{{ $error }}</p>
      @endforeach
    </div>
  @endif

  {{-- enctype e obrigatorio: sem ele o navegador manda so o nome do arquivo --}}
  <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
    @csrf

    <section class="settings-card settings-anim" id="perfil" style="animation-delay: .11s">
      <div class="settings-card__head">
        <span class="settings-badge">
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21v-2a5 5 0 0 1 5-5h8a5 5 0 0 1 5 5v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        </span>
        <div>
          <h2 class="settings-card__heading">Perfil</h2>
          <p class="settings-card__desc">Foto e dados de identificação.</p>
        </div>
      </div>

      <div class="settings-avatar-row">
        <div class="settings-avatar-wrap">
          @if ($user->foto)
            <img class="store-logo store-logo--lg" id="avatar-preview" src="{{ asset($user->foto) }}" alt="Sua foto de perfil">
          @else
            <span class="store-logo store-logo--lg store-logo--initials" id="avatar-preview-initials" aria-hidden="true">{{ $user->iniciais() }}</span>
            <img class="store-logo store-logo--lg" id="avatar-preview" src="" alt="Pré-visualização da foto" hidden>
          @endif

          {{-- o label envolve o input escondido: clicar no selo da camera abre o seletor de arquivo --}}
          <label class="settings-avatar-badge" for="foto" title="Alterar foto">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2Z"></path><circle cx="12" cy="13" r="4"></circle></svg>
          </label>
          <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp" class="settings-file-input">
        </div>

        <div class="settings-avatar-meta">
          <span class="avatar-field__hint">JPG, PNG ou WEBP, até 5 MB.</span>

          @if ($user->foto)
            <label class="checkbox settings-remove-link">
              <input type="checkbox" name="remover_foto" value="1">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><path d="m19 6-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path></svg>
              Remover foto atual
            </label>
          @endif
        </div>
      </div>

      <div class="settings-grid">
        <div class="field">
          <label for="name">Nome</label>
          <div class="settings-field-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21v-2a5 5 0 0 1 5-5h8a5 5 0 0 1 5 5v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            <input type="text" id="name" name="name" value="{{ old('name', $user->nome) }}" required>
          </div>
        </div>

        <div class="field">
          <label for="email">E-mail</label>
          <div class="settings-field-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M22 6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2Z"></path><path d="m2 7 10 6 10-6"></path></svg>
            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
          </div>
        </div>
      </div>
    </section>

    <section class="settings-card settings-anim" id="endereco" style="animation-delay: .14s">
      <div class="settings-card__head">
        <span class="settings-badge">
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>
        </span>
        <div>
          <h2 class="settings-card__heading">Endereço</h2>
          <p class="settings-card__desc">Usado para calcular o frete e entregar seus pedidos.</p>
        </div>
      </div>

      @include('partials.address-fields', ['obrigatorio' => false, 'enderecoAtual' => $user->endereco])
    </section>

    <section class="settings-card settings-anim" id="conta" style="animation-delay: .17s">
      <div class="settings-card__head">
        <span class="settings-badge">
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"></path></svg>
        </span>
        <div>
          <h2 class="settings-card__heading">Conta</h2>
          <p class="settings-card__desc">Permanência e acesso na HR Moda Online.</p>
        </div>
      </div>

      <div class="settings-account-row">
        <div class="settings-account-row__label">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
          Cliente desde
        </div>
        <span class="settings-account-row__value">{{ auth()->user()->created_at->format('d/m/Y') }}</span>
      </div>

      <div class="settings-account-row">
        <div class="settings-account-row__label">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5"></path><line x1="21" y1="12" x2="9" y2="12"></line></svg>
          Sair da sua conta
        </div>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="settings-account-row__action">Sair</button>
        </form>
      </div>
    </section>

    <div class="settings-savebar settings-anim" style="animation-delay: .2s">
      <button type="submit" class="btn btn-primary settings-save-btn">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>
        Salvar alterações
      </button>
    </div>
  </form>
</section>
@endsection

{{-- em @push('scripts') porque o ajax-nav reexecuta essa pilha a cada troca de
     pagina; um <script> solto no content so rodaria no carregamento inicial --}}
@push('scripts')
  <script src="{{ asset_v('js/avatar-preview.js') }}"></script>
@endpush
