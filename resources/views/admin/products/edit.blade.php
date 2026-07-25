@extends('layouts.admin')

@section('title', 'Editar peça · Painel HR')

@section('content')
<div class="admin-wrap">
  <div class="auth-card product-form-card">
    <h1 class="auth-card__title">Editar peça</h1>
    <p class="auth-card__subtitle">Atualize os dados de "{{ $product->nome }}".</p>

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

    <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
      @csrf
      @method('PUT')

      @include('admin.products._form', ['product' => $product])

      <button type="submit" class="btn btn-primary btn-block">Salvar alterações</button>
    </form>
  </div>
</div>
@endsection
