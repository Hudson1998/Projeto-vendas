@extends('layouts.loja')

@section('title', 'Pedidos · Painel da Loja')

@section('content')
<div class="admin-wrap">
  <div class="admin-page-title">
    <h1>Pedidos</h1>
  </div>

  <div class="admin-panel">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Pedido</th>
          <th>Cliente</th>
          <th>Total</th>
          <th>Separação</th>
          <th>Entrega</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($pedidos as $pedido)
          <tr>
            <td>#{{ $pedido->id }}</td>
            <td>{{ $pedido->user->name }}</td>
            <td>R$ {{ number_format($pedido->total + ($pedido->valor_frete ?? 0), 2, ',', '.') }}</td>
            <td>{{ $pedido->status_separacao ?? 'aguardando' }}</td>
            <td>
              @if ($pedido->entrega_propria === true)
                Entrega própria
              @elseif ($pedido->transportadora)
                {{ $pedido->transportadora->nome }}
              @else
                não definida
              @endif
            </td>
            <td class="admin-table__actions">
              <form method="POST" action="{{ route('loja.pedidos.entrega', $pedido) }}" class="loja-inline-form">
                @csrf
                <select name="entrega_propria" onchange="this.form.transportadora_id.disabled = this.value === '1'">
                  <option value="1" @selected($pedido->entrega_propria === true)>Entrega própria</option>
                  <option value="0" @selected($pedido->entrega_propria === false)>Transportadora</option>
                </select>
                <select name="transportadora_id" @disabled($pedido->entrega_propria !== false)>
                  <option value="">Selecione</option>
                  @foreach ($transportadoras as $t)
                    <option value="{{ $t->id }}" @selected($pedido->transportadora_id === $t->id)>{{ $t->nome }}</option>
                  @endforeach
                </select>
                <button type="submit" class="btn btn-outline">Salvar</button>
              </form>

              <form method="POST" action="{{ route('loja.pedidos.separar', $pedido) }}" class="loja-inline-form">
                @csrf
                <button type="submit" class="btn btn-outline">Separar</button>
              </form>

              <form method="POST" action="{{ route('loja.pedidos.embalar', $pedido) }}" class="loja-inline-form">
                @csrf
                <label class="checkbox"><input type="checkbox" name="fragil" value="1" @checked($pedido->fragil)> Frágil</label>
                <input type="text" name="dimensoes" placeholder="Dimensões (ex: 30x20x10cm)" value="{{ $pedido->dimensoes }}">
                <button type="submit" class="btn btn-outline">Embalar</button>
              </form>

              <form method="POST" action="{{ route('loja.pedidos.enviar', $pedido) }}" class="loja-inline-form">
                @csrf
                <button type="submit" class="btn btn-primary">Despachar</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="admin-table__empty">Nenhum pedido aprovado no momento.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
