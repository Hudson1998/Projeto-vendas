@extends('layouts.loja')

@section('title', 'Visitantes · Painel da Loja')

@section('content')
<div class="admin-wrap">
  <div class="admin-page-title">
    <h1>Clientes que visitaram sua loja</h1>
    <span class="admin-live-indicator">{{ $visitantes->count() }} visitante(s)</span>
  </div>

  <div class="admin-panel">
    <table class="admin-table">
      <thead><tr><th>Nome</th><th>E-mail</th></tr></thead>
      <tbody>
        @forelse ($visitantes as $cliente)
          <tr><td>{{ $cliente->name }}</td><td>{{ $cliente->email }}</td></tr>
        @empty
          <tr><td colspan="2" class="admin-table__empty">Nenhum visitante identificado ainda.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
