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

      @include('admin.products._form', ['product' => null])

      <button type="submit" class="btn btn-primary btn-block">Cadastrar peça</button>
    </form>
  </div>
</div>
@endsection
