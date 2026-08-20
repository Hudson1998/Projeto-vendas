@extends('layouts.loja')

@section('title', 'Produtos · Painel da Loja')

@section('content')
<div class="admin-wrap">
  <div class="admin-page-title">
    <h1>Produtos da loja</h1>
  </div>

  <div class="admin-grid">
    <div class="admin-panel">
      <h2 class="admin-panel__title">Todos os produtos</h2>
      <table class="admin-table">
        <thead><tr><th>Nome</th><th>Categoria</th><th>Preço</th></tr></thead>
        <tbody>
          @forelse ($produtos as $p)
            <tr><td>{{ $p->nome }}</td><td>{{ $p->categoria }}</td><td>R$ {{ number_format($p->preco, 2, ',', '.') }}</td></tr>
          @empty
            <tr><td colspan="3" class="admin-table__empty">Nenhum produto cadastrado ainda.</td></tr>
          @endforelse
        </tbody>
      </table>
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
      <h2 class="admin-panel__title">Mais visitados</h2>
      <ul class="admin-rank">
        @forelse ($maisVisitados as $p)
          <li><span>{{ $p->nome }}</span></li>
        @empty
          <li class="admin-table__empty">Nenhuma visita registrada ainda.</li>
        @endforelse
      </ul>
    </div>
  </div>
</div>
@endsection
