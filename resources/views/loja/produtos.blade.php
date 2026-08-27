@extends('layouts.loja')

@section('title', 'Produtos · Painel da Loja')

@section('content')
<div class="admin-wrap">
  <div class="admin-page-title">
    <h1>Produtos da loja</h1>
  </div>

  @if (session('status'))
    <div class="form-status">{{ session('status') }}</div>
  @endif

  @if ($errors->any())
    <div class="form-status form-status--error">
      @foreach ($errors->all() as $erro)
        <p>{{ $erro }}</p>
      @endforeach
    </div>
  @endif

  <div class="admin-grid">
    <div class="admin-panel">
      <h2 class="admin-panel__title">Cadastrar peça</h2>

      <form method="POST" action="{{ route('loja.produtos.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="field">
          <label for="nome">Nome da peça</label>
          <input type="text" id="nome" name="nome" maxlength="255" value="{{ old('nome') }}" required>
        </div>

        <div class="field">
          <label for="categoria">Categoria</label>
          <input type="text" id="categoria" name="categoria" list="categorias-existentes"
                 maxlength="255" value="{{ old('categoria') }}" required>
          <datalist id="categorias-existentes">
            @foreach ($categorias as $c)
              <option value="{{ $c }}"></option>
            @endforeach
          </datalist>
        </div>

        <div class="field">
          <label for="preco">Preço (R$)</label>
          <input type="number" id="preco" name="preco" step="0.01" min="0.01" value="{{ old('preco') }}" required>
        </div>

        <div class="field">
          <label for="foto">Foto</label>
          <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp" required>
          <span class="field__hint">JPG, PNG ou WEBP, até 5 MB.</span>
        </div>

        <div class="field">
          <label for="descricao">Descrição</label>
          <textarea id="descricao" name="descricao" rows="3" maxlength="5000">{{ old('descricao') }}</textarea>
        </div>

        {{-- o estoque vive em product_variants: uma peca sem variante aparece
             na vitrine e nao pode ser comprada, entao a primeira sai daqui --}}
        <div class="cartao-form__linha">
          <div class="field">
            <label for="tamanho">Tamanho</label>
            <input type="text" id="tamanho" name="tamanho" maxlength="60" placeholder="P, M, 38…" value="{{ old('tamanho') }}">
          </div>
          <div class="field">
            <label for="cor">Cor</label>
            <input type="text" id="cor" name="cor" maxlength="60" placeholder="Preto" value="{{ old('cor') }}">
          </div>
        </div>

        <div class="field">
          <label for="estoque">Estoque desta variante</label>
          <input type="number" id="estoque" name="estoque" min="0" value="{{ old('estoque', 1) }}" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Cadastrar peça</button>
      </form>
    </div>

    <div class="admin-panel admin-panel--largo">
      <h2 class="admin-panel__title">Catálogo ({{ $produtos->count() }})</h2>
      <table class="admin-table">
        <thead><tr><th>Peça</th><th>Categoria</th><th>Preço</th><th>Estoque</th><th></th></tr></thead>
        <tbody>
          @forelse ($produtos as $p)
            <tr>
              <td class="produto-celula">
                <img src="{{ asset($p->imagem) }}" alt="" class="produto-celula__foto">
                <span>{{ $p->nome }}</span>
              </td>
              <td>{{ $p->categoria }}</td>
              <td>R$ {{ number_format($p->preco, 2, ',', '.') }}</td>
              <td>{{ $p->variants->sum('estoque') }}</td>
              <td>
                <form method="POST" action="{{ route('loja.produtos.destroy', $p) }}" class="loja-inline-form">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="link-btn link-btn--danger">Remover</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="admin-table__empty">Nenhuma peça cadastrada ainda.</td></tr>
          @endforelse
        </tbody>
      </table>
      <p class="carteira-aviso">
        Peça já vendida não pode ser removida — o histórico dos pedidos aponta para
        ela. Zere o estoque para tirá-la da vitrine.
      </p>
    </div>

    <div class="admin-panel">
      <h2 class="admin-panel__title">Mais vendidos</h2>
      <ul class="admin-rank">
        @forelse ($maisVendidos as $p)
          <li><span>{{ $p->nome }}</span><span class="admin-rank__count">{{ $p->quantidade_vendida }}</span></li>
        @empty
          <li class="admin-table__empty">Nenhuma venda registrada ainda.</li>
        @endforelse
      </ul>
    </div>

    <div class="admin-panel">
      <h2 class="admin-panel__title">Visitas por produto</h2>
      <ul class="admin-rank">
        @forelse ($visitasPorProduto as $item)
          <li><span>{{ $item['nome'] }}</span><span class="admin-rank__count">{{ $item['visitas'] }}</span></li>
        @empty
          <li class="admin-table__empty">Nenhuma visita registrada ainda.</li>
        @endforelse
      </ul>
    </div>
  </div>
</div>
@endsection
