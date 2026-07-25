@extends('layouts.app')

@section('title', 'Meu carrinho · HR Moda Feminina')

@section('content')
<section class="wrap page-section">
  <div class="page-header">
    <h1 class="page-header__title">Meu carrinho</h1>
  </div>

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

  @if ($itens->isEmpty())
    <div class="empty-state is-visible">
      <span class="empty-state__title">Seu carrinho está vazio</span>
      <span class="empty-state__subtitle">Volte à coleção e escolha suas peças favoritas.</span>
      <a href="{{ route('home') }}#colecao" class="btn btn-outline" style="margin-top: 20px;">Ver coleção</a>
    </div>
  @else
    <div class="cart-layout">
      <div class="cart-items">
        <div class="cart-items__header">
          <span class="cart-items__count">{{ $itens->count() }} {{ $itens->count() === 1 ? 'item' : 'itens' }}</span>
          <form method="POST" action="{{ route('cart.clear') }}" id="clear-cart-form">
            @csrf
            @method('DELETE')
            <button type="button" class="link-btn link-btn--danger" id="clear-cart-btn">Limpar carrinho</button>
          </form>
        </div>
        @foreach ($itens as $item)
          <div class="cart-item">
            <img class="cart-item__image" src="{{ asset($item->product->imagem) }}" alt="{{ $item->product->nome }}">
            <div class="cart-item__body">
              <span class="cart-item__category">{{ $item->product->categoria }}</span>
              <span class="cart-item__name">{{ $item->product->nome }}</span>
              @if ($item->tamanho || $item->cor)
                <span class="cart-item__variant">
                  {{ collect([$item->tamanho, $item->cor])->filter()->implode(' · ') }}
                </span>
              @endif
              <span class="cart-item__price">R$ {{ number_format($item->product->preco, 2, ',', '.') }} / un.</span>

              <div class="cart-item__actions">
                <form method="POST" action="{{ route('cart.update', $item) }}" class="cart-item__qty-form">
                  @csrf
                  @method('PATCH')
                  <label for="qtd-{{ $item->id }}">Qtd.</label>
                  <input type="number" id="qtd-{{ $item->id }}" name="quantidade" value="{{ $item->quantidade }}" min="1" max="20">
                  <button type="submit" class="link-btn">Atualizar</button>
                </form>

                <form method="POST" action="{{ route('cart.destroy', $item) }}">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="link-btn link-btn--danger">Remover</button>
                </form>
              </div>
            </div>
            <span class="cart-item__subtotal">R$ {{ number_format($item->product->preco * $item->quantidade, 2, ',', '.') }}</span>
          </div>
        @endforeach
      </div>

      <div class="cart-summary">
        <h2 class="cart-summary__title">Finalizar pedido</h2>

        <form method="POST" action="{{ route('cart.checkout') }}">
          @csrf

          <div class="field">
            <label>Forma de pagamento</label>
            <div class="radio-group">
              <label class="radio"><input type="radio" name="forma_pagamento" value="pix" checked> Pix</label>
              <label class="radio"><input type="radio" name="forma_pagamento" value="cartao"> Cartão de crédito</label>
              <label class="radio"><input type="radio" name="forma_pagamento" value="boleto"> Boleto</label>
            </div>
          </div>

          <div class="field">
            <label>Entrega</label>
            <div class="radio-group">
              <label class="radio"><input type="radio" name="tipo_entrega" value="retirada" checked data-toggle-endereco> Retirar na loja</label>
              <label class="radio"><input type="radio" name="tipo_entrega" value="entrega" data-toggle-endereco> Receber em casa</label>
            </div>
          </div>

          <div class="field" id="bloco-endereco" style="display: none;">
            <label>Endereço de entrega</label>
            @if (auth()->user()->endereco)
              <p class="cart-summary__endereco">{{ auth()->user()->endereco }}</p>
              <a href="{{ route('profile.edit') }}" class="link-btn">Alterar endereço</a>
            @else
              <p class="cart-summary__endereco cart-summary__endereco--vazio">Nenhum endereço cadastrado.</p>
              <a href="{{ route('profile.edit') }}" class="link-btn">Cadastrar endereço</a>
            @endif
          </div>

          <div class="cart-summary__total">
            <span>Total</span>
            <span>R$ {{ number_format($total, 2, ',', '.') }}</span>
          </div>

          <button type="submit" class="btn btn-primary btn-block">Confirmar pedido</button>
        </form>
      </div>
    </div>
  @endif
</section>

<script>
  document.querySelectorAll('[data-toggle-endereco]').forEach((radio) => {
    radio.addEventListener('change', () => {
      document.getElementById('bloco-endereco').style.display =
        document.querySelector('input[name="tipo_entrega"]:checked').value === 'entrega' ? 'block' : 'none';
    });
  });

  document.getElementById('clear-cart-btn')?.addEventListener('click', () => {
    confirmarAcao({
      titulo: 'Limpar carrinho',
      mensagem: 'Tem certeza que deseja remover todos os itens do carrinho?',
      textoConfirmar: 'Limpar',
      textoCancelar: 'Desistir',
      onConfirm: () => document.getElementById('clear-cart-form').submit(),
    });
  });
</script>
@endsection
