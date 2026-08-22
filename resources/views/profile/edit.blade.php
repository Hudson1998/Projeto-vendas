@extends('layouts.app')

@section('title', 'Configurações · HR Moda Online')

@section('content')
<section class="wrap page-section">
  <div class="page-header">
    <h1 class="page-header__title">Configurações</h1>
  </div>

  <div class="auth-card product-form-card" style="margin: 0;">
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

      <div class="field">
        <label>Foto de perfil</label>
        <div class="avatar-field">
          @if ($user->foto)
            <img class="store-logo store-logo--lg" id="avatar-preview" src="{{ asset($user->foto) }}" alt="Sua foto de perfil">
          @else
            <span class="store-logo store-logo--lg store-logo--initials" id="avatar-preview-initials" aria-hidden="true">{{ $user->iniciais() }}</span>
            <img class="store-logo store-logo--lg" id="avatar-preview" src="" alt="Pré-visualização da foto" hidden>
          @endif

          <div class="avatar-field__actions">
            <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp" class="avatar-field__input">
            <span class="avatar-field__hint">JPG, PNG ou WEBP, até 5 MB.</span>

            @if ($user->foto)
              <label class="checkbox avatar-field__remove">
                <input type="checkbox" name="remover_foto" value="1">
                Remover foto atual
              </label>
            @endif
          </div>
        </div>
      </div>

      <div class="field">
        <label for="name">Nome</label>
        <input type="text" id="name" name="name" value="{{ old('name', $user->nome) }}" required>
      </div>

      <div class="field">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
      </div>

      @include('partials.address-fields', ['obrigatorio' => false, 'enderecoAtual' => $user->endereco])

      <button type="submit" class="btn btn-primary btn-block">Salvar alterações</button>
    </form>
  </div>
</section>
@endsection

{{-- em @push('scripts') porque o ajax-nav reexecuta essa pilha a cada troca de
     pagina; um <script> solto no content so rodaria no carregamento inicial --}}
@push('scripts')
  <script src="{{ asset_v('js/avatar-preview.js') }}"></script>
@endpush
