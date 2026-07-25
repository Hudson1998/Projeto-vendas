@extends('layouts.admin')

@section('title', 'Clientes cadastrados · Painel HR')

@section('content')
<div class="admin-wrap">
  <div class="admin-page-title">
    <h1>Clientes cadastrados</h1>
    <span class="admin-live-indicator">{{ $clientes->count() }} {{ $clientes->count() === 1 ? 'cliente' : 'clientes' }}</span>
  </div>

  <div class="admin-panel admin-panel--full">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Nome</th>
          <th>E-mail</th>
          <th>Endereço</th>
          <th>Pedidos</th>
          <th>Total gasto</th>
          <th>Cadastrado em</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($clientes as $cliente)
          <tr>
            <td><strong>{{ $cliente->name }}</strong></td>
            <td>{{ $cliente->email }}</td>
            <td>{{ $cliente->endereco ?? '—' }}</td>
            <td>{{ $cliente->orders_count }}</td>
            <td>R$ {{ number_format($cliente->total_gasto ?? 0, 2, ',', '.') }}</td>
            <td>{{ $cliente->created_at->format('d/m/Y H:i') }}</td>
          </tr>
        @empty
          <tr><td colspan="6" class="admin-table__empty">Nenhum cliente cadastrado ainda.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
