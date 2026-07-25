@extends('layouts.admin')

@section('title', 'Nova peça · Painel HR')

@section('content')
<div class="admin-wrap">
  <div class="auth-card product-form-card">
    <h1 class="auth-card__title">Nova peça</h1>
    <p class="auth-card__subtitle">Cadastre um novo item da coleção.</p>

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

    <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
      @csrf

      <div class="field">
        <label for="nome">Nome da peça</label>
        <input type="text" id="nome" name="nome" value="{{ old('nome') }}" required autofocus>
      </div>

      <div class="field">
        <label for="categoria">Tipo de roupa</label>
        <select id="categoria" name="categoria" required>
          <option value="" disabled {{ old('categoria') ? '' : 'selected' }}>Selecione uma categoria</option>
          @foreach (['Camisas', 'Calças', 'Vestidos', 'Saias', 'Acessórios', 'Chapéus', 'Perfumes', 'Calçados'] as $opcao)
            <option value="{{ $opcao }}" @selected(old('categoria') === $opcao)>{{ $opcao }}</option>
          @endforeach
        </select>
      </div>

      <div class="field">
        <label for="preco">Preço de venda (R$)</label>
        <input type="number" id="preco" name="preco" value="{{ old('preco') }}" step="0.01" min="0" placeholder="0,00" required>
      </div>

      <div class="field">
        <label for="custo">Custo de compra (R$)</label>
        <input type="number" id="custo" name="custo" value="{{ old('custo') }}" step="0.01" min="0" placeholder="0,00">
      </div>

      <div class="field">
        <label for="foto">Foto da peça</label>
        <input type="file" id="foto" name="foto" accept="image/png,image/jpeg,image/webp" required>
      </div>

      <button type="submit" class="btn btn-primary btn-block">Cadastrar peça</button>
    </form>
  </div>
</div>
@endsection
