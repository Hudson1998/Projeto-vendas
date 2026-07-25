@extends('layouts.admin')

@section('title', 'Dados · Painel HR')

@section('content')
<div class="admin-wrap">
  <div class="admin-page-title">
    <h1>Dados</h1>
    <span class="admin-live-indicator"><span class="admin-live-dot"></span> Atualizando ao vivo</span>
  </div>

  <div class="stat-grid">
    <div class="stat-tile">
      <span class="stat-tile__label">Acessos totais</span>
      <span class="stat-tile__value" id="stat-totalVisitas">{{ $totalVisitas }}</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Acessos hoje</span>
      <span class="stat-tile__value" id="stat-visitasHoje">{{ $visitasHoje }}</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Visitantes únicos</span>
      <span class="stat-tile__value" id="stat-visitantesUnicos">{{ $visitantesUnicos }}</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Clientes cadastrados</span>
      <span class="stat-tile__value" id="stat-totalCadastrados">{{ $totalCadastrados }}</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Cadastros hoje</span>
      <span class="stat-tile__value" id="stat-cadastrosHoje">{{ $cadastrosHoje }}</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Pedidos realizados</span>
      <span class="stat-tile__value" id="stat-totalPedidos">{{ $totalPedidos }}</span>
    </div>
  </div>

  <div class="admin-grid">
    <div class="admin-panel">
      <h2 class="admin-panel__title">Últimos cadastros</h2>
      <table class="admin-table">
        <thead>
          <tr><th>Nome</th><th>E-mail</th><th>Data</th></tr>
        </thead>
        <tbody id="tbody-ultimosCadastros">
          @forelse ($ultimosCadastros as $c)
            <tr><td>{{ $c['nome'] }}</td><td>{{ $c['email'] }}</td><td>{{ $c['data'] }}</td></tr>
          @empty
            <tr><td colspan="3" class="admin-table__empty">Nenhum cadastro ainda.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="admin-panel">
      <h2 class="admin-panel__title">Últimas visitas</h2>
      <table class="admin-table">
        <thead>
          <tr><th>Página</th><th>IP</th><th>Usuário</th><th>Data</th></tr>
        </thead>
        <tbody id="tbody-ultimasVisitas">
          @forelse ($ultimasVisitas as $v)
            <tr><td>{{ $v['caminho'] }}</td><td>{{ $v['ip'] }}</td><td>{{ $v['usuario'] ?? '—' }}</td><td>{{ $v['data'] }}</td></tr>
          @empty
            <tr><td colspan="4" class="admin-table__empty">Nenhuma visita registrada ainda.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="admin-panel">
      <h2 class="admin-panel__title">Termos mais buscados</h2>
      <ul class="admin-rank" id="list-termosMaisBuscados">
        @forelse ($termosMaisBuscados as $t)
          <li><span>{{ $t->termo }}</span><span class="admin-rank__count">{{ $t->total }}</span></li>
        @empty
          <li class="admin-table__empty">Nenhuma busca registrada ainda.</li>
        @endforelse
      </ul>
    </div>

    <div class="admin-panel">
      <h2 class="admin-panel__title">Produtos mais vendidos</h2>
      <ul class="admin-rank" id="list-produtosMaisVendidos">
        @forelse ($produtosMaisVendidos as $p)
          <li><span>{{ $p->nome }}</span><span class="admin-rank__count">{{ $p->total_vendido }}</span></li>
        @empty
          <li class="admin-table__empty">Nenhuma venda registrada ainda.</li>
        @endforelse
      </ul>
    </div>
  </div>
</div>

<script>
  window.ADMIN_STATS_URL = "{{ route('admin.stats') }}";
</script>
<script src="{{ asset('js/admin.js') }}"></script>
@endsection
