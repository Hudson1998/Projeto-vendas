@extends('layouts.loja')

@section('title', 'Transportadoras · Painel da Loja')

@section('content')
<div class="admin-wrap">
  <div class="admin-page-title">
    <h1>Transportadoras</h1>
  </div>

  <div class="admin-grid">
    <div class="admin-panel">
      <h2 class="admin-panel__title">Parceiras vinculadas à sua loja</h2>
      <table class="admin-table">
        <thead><tr><th>Nome</th><th>Telefone</th><th>Ação</th></tr></thead>
        <tbody>
          @forelse ($vinculadas as $t)
            <tr>
              <td>{{ $t->nome }}</td>
              <td>{{ $t->telefone ?? '—' }}</td>
              <td>
                <form method="POST" action="{{ route('loja.transportadoras.desvincular', $t) }}">
                  @csrf
                  <button type="submit" class="btn btn-outline">Desvincular</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="3" class="admin-table__empty">Nenhuma transportadora vinculada ainda.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="admin-panel">
      <h2 class="admin-panel__title">Transportadoras disponíveis</h2>
      <table class="admin-table">
        <thead><tr><th>Nome</th><th>Área de atuação</th><th>Ação</th></tr></thead>
        <tbody>
          @forelse ($disponiveis as $t)
            <tr>
              <td>{{ $t->nome }}</td>
              <td>{{ $t->area_atuacao ?? '—' }}</td>
              <td>
                <form method="POST" action="{{ route('loja.transportadoras.vincular', $t) }}">
                  @csrf
                  <button type="submit" class="btn btn-outline">Vincular</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="3" class="admin-table__empty">Nenhuma transportadora cadastrada no sistema ainda.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="admin-panel">
      <h2 class="admin-panel__title">Cadastrar nova transportadora</h2>
      <form method="POST" action="{{ route('loja.transportadoras.store') }}" class="product-form-card">
        @csrf
        <div class="field"><label>Nome</label><input type="text" name="nome" required></div>
        <div class="field"><label>CNPJ</label><input type="text" name="cnpj"></div>
        <div class="field"><label>Telefone</label><input type="text" name="telefone"></div>
        <div class="field"><label>E-mail</label><input type="email" name="email"></div>
        <div class="field"><label>Tipo de veículo</label><input type="text" name="tipo_veiculo" placeholder="Moto, carro, van..."></div>
        <div class="field"><label>Área de atuação</label><input type="text" name="area_atuacao" placeholder="Cidade/região"></div>
        <button type="submit" class="btn btn-primary">Cadastrar</button>
      </form>
    </div>

    <div class="admin-panel">
      <h2 class="admin-panel__title">Cadastrar motorista</h2>
      <form method="POST" action="{{ route('loja.motoristas.store') }}" class="product-form-card">
        @csrf
        <div class="field">
          <label>Transportadora (opcional)</label>
          <select name="transportadora_id">
            <option value="">Motorista independente</option>
            @foreach ($vinculadas as $t)
              <option value="{{ $t->id }}">{{ $t->nome }}</option>
            @endforeach
          </select>
        </div>
        <div class="field"><label>Nome</label><input type="text" name="nome" required></div>
        <div class="field"><label>CPF</label><input type="text" name="cpf"></div>
        <div class="field"><label>CNH</label><input type="text" name="cnh"></div>
        <div class="field"><label>Telefone</label><input type="text" name="telefone"></div>
        <button type="submit" class="btn btn-primary">Cadastrar</button>
      </form>
    </div>
  </div>
</div>
@endsection
