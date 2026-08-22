@extends('layouts.loja')

@section('title', 'Perfil da Loja · Painel da Loja')

@section('content')
<div class="admin-wrap">
  <div class="admin-page-title">
    <h1>Perfil da loja</h1>
    <a href="{{ route('lojas.show', $loja) }}" class="btn btn-outline">Ver vitrine pública</a>
  </div>

  <div class="admin-panel">
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

    <p class="admin-hint">
      É isto que o comprador vê na sua vitrine e no bloco “Vendido por” de cada peça.
    </p>

    {{-- enctype e obrigatorio: sem ele o navegador manda so o nome do arquivo --}}
    <form method="POST" action="{{ route('loja.perfil.update') }}" enctype="multipart/form-data">
      @csrf

      <div class="field">
        <label>Logotipo</label>
        <div class="avatar-field">
          @if ($loja->logotipo)
            <img class="store-logo store-logo--lg" id="avatar-preview" src="{{ asset($loja->logotipo) }}" alt="Logotipo de {{ $loja->nomeExibicao() }}">
          @else
            <span class="store-logo store-logo--lg store-logo--initials" id="avatar-preview-initials" aria-hidden="true">{{ $loja->iniciais() }}</span>
            <img class="store-logo store-logo--lg" id="avatar-preview" src="" alt="Pré-visualização do logotipo" hidden>
          @endif

          <div class="avatar-field__actions">
            {{-- id "foto" e a convencao que o avatar-preview.js espera --}}
            <input type="file" id="foto" name="logotipo" accept="image/jpeg,image/png,image/webp" class="avatar-field__input">
            <span class="avatar-field__hint">JPG, PNG ou WEBP, até 5 MB. Quadrado fica melhor.</span>

            @if ($loja->logotipo)
              <label class="checkbox avatar-field__remove">
                <input type="checkbox" name="remover_logotipo" value="1">
                Remover logotipo atual
              </label>
            @endif
          </div>
        </div>
      </div>

      <div class="field">
        <label for="nome_exibicao">Nome de exibição</label>
        <input type="text" id="nome_exibicao" name="nome_exibicao" maxlength="255"
               value="{{ old('nome_exibicao', $loja->nome_exibicao) }}"
               placeholder="{{ $loja->nome_fantasia }}">
        <span class="avatar-field__hint">Se deixar em branco, a vitrine usa “{{ $loja->nome_fantasia }}”.</span>
      </div>

      <div class="field">
        <label for="bio_loja">Sobre a loja</label>
        <textarea id="bio_loja" name="bio_loja" rows="4" maxlength="2000" required>{{ old('bio_loja', $loja->bio_loja) }}</textarea>
      </div>

      <button type="submit" class="btn btn-primary">Salvar alterações</button>
    </form>
  </div>
</div>
@endsection

{{-- em @push('scripts') porque o ajax-nav reexecuta essa pilha a cada troca de
     pagina; um <script> solto no content so rodaria no carregamento inicial --}}
@push('scripts')
  <script src="{{ asset_v('js/avatar-preview.js') }}"></script>
@endpush
