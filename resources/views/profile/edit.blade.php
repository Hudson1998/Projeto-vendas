@extends('layouts.app')

@section('title', 'Configurações · HR Moda Feminina')

@section('content')
<section class="wrap page-section">
  <div class="page-header">
    <h1 class="page-header__title">Configurações</h1>
  </div>

  <div class="auth-card product-form-card" style="margin: 0;">
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

    <form method="POST" action="{{ route('profile.update') }}">
      @csrf

      <div class="field">
        <label for="name">Nome</label>
        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
      </div>

      <div class="field">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
      </div>

      <div class="field">
        <label for="endereco">Endereço de entrega</label>
        <input type="text" id="endereco" name="endereco" value="{{ old('endereco', $user->endereco) }}" required>
      </div>

      <button type="submit" class="btn btn-primary btn-block">Salvar alterações</button>
    </form>
  </div>
</section>
@endsection
