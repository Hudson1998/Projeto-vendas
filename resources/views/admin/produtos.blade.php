@extends('layouts.admin')

@section('title', 'Produtos cadastrados · Painel HR')

@section('content')
<div class="admin-wrap">
  <div class="admin-page-title">
    <h1>Produtos cadastrados</h1>
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary" style="padding: 12px 24px;">+ Nova peça</a>
  </div>

  @if (session('status'))
    <div class="form-status">{{ session('status') }}</div>
  @endif

  <div class="admin-panel admin-panel--full">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Foto</th>
          <th>Nome</th>
          <th>Categoria</th>
          <th>Preço</th>
          <th>Custo</th>
          <th>Estoque</th>
          <th>Cadastrado em</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse ($produtos as $produto)
          <tr>
            <td><img class="admin-table__thumb" src="{{ asset($produto->imagem) }}" alt="{{ $produto->nome }}"></td>
            <td><strong>{{ $produto->nome }}</strong></td>
            <td>{{ $produto->categoria }}</td>
            <td>R$ {{ number_format($produto->preco, 2, ',', '.') }}</td>
            <td>
              <form method="POST" action="{{ route('admin.products.custo', $produto) }}" class="custo-form">
                @csrf
                @method('PATCH')
                <span class="custo-form__prefix">R$</span>
                <input type="number" name="custo" step="0.01" min="0" value="{{ $produto->custo }}">
                <button type="submit" class="link-btn">Salvar</button>
              </form>
            </td>
            <td>{{ $produto->estoque }}</td>
            <td>{{ $produto->created_at->format('d/m/Y H:i') }}</td>
            <td><a href="{{ route('admin.products.edit', $produto) }}" class="link-btn">Editar</a></td>
          </tr>
        @empty
          <tr><td colspan="8" class="admin-table__empty">Nenhum produto cadastrado ainda.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
