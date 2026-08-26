@extends('layouts.app')

@section('title', 'Pagamento do pedido #'.$pedido->id.' · HR Moda Online')

@section('content')
@php
  $forma = $cobranca['forma'];
  $rotulos = ['pix' => 'Pix', 'boleto' => 'Boleto bancário', 'cartao' => 'Cartão de crédito'];
@endphp

<section class="wrap page-section">
  <div class="page-header">
    <span class="page-header__eyebrow">Pedido #{{ $pedido->id }}</span>
    <h1 class="page-header__title">Pagamento via {{ $rotulos[$forma] }}</h1>
  </div>

  @if ($errors->any())
    <div class="form-status form-status--error">
      @foreach ($errors->all() as $error)
        <p>{{ $error }}</p>
      @endforeach
    </div>
  @endif

  <div class="pagamento-layout">
    <div class="pagamento-principal">

      {{-- ================= PIX ================= --}}
      @if ($forma === 'pix')
        <div class="pagamento-card">
          <h2 class="pagamento-card__titulo">Aponte a câmera para o QR Code</h2>
          <p class="pagamento-card__ajuda">
            Abra o app do seu banco, escolha pagar com Pix e leia o código abaixo.
          </p>

          <div class="pagamento-qrcode">
            {!! $instrumento['qrcode'] !!}
          </div>

          <div class="pagamento-codigo">
            <span class="pagamento-codigo__rotulo">Ou copie o código Pix</span>
            <code class="pagamento-codigo__valor" id="pix-copia-cola">{{ $cobranca['instrucao'] }}</code>
            <button type="button" class="btn btn-outline btn-block" data-copiar="#pix-copia-cola">
              Copiar código
            </button>
          </div>
        </div>

      {{-- ================= BOLETO ================= --}}
      @elseif ($forma === 'boleto')
        <div class="pagamento-card">
          <h2 class="pagamento-card__titulo">Boleto bancário</h2>
          <p class="pagamento-card__ajuda">
            Vence em {{ $cobranca['vencimento'] }}. Pague no app do banco, no caixa
            eletrônico ou em uma lotérica.
          </p>

          <div class="pagamento-barras">
            {!! $instrumento['barras'] !!}
          </div>

          <div class="pagamento-codigo">
            <span class="pagamento-codigo__rotulo">Linha digitável</span>
            <code class="pagamento-codigo__valor pagamento-codigo__valor--linha" id="boleto-linha">{{ $instrumento['linha_digitavel'] }}</code>
            {{-- o botao copia os 47 digitos limpos; a mascara e so para ler --}}
            <span hidden id="boleto-linha-limpa">{{ $cobranca['instrucao'] }}</span>
            <button type="button" class="btn btn-outline btn-block" data-copiar="#boleto-linha-limpa">
              Copiar linha digitável
            </button>
          </div>
        </div>

      {{-- ================= CARTAO ================= --}}
      @else
        <div class="pagamento-card">
          <h2 class="pagamento-card__titulo">Dados do cartão</h2>
          <p class="pagamento-card__ajuda">
            A cobrança é autorizada na hora e o pedido segue direto para a loja.
          </p>

          <div class="cartao-preview" id="cartao-preview">
            <div class="cartao-preview__chip"></div>
            <span class="cartao-preview__bandeira" id="cartao-bandeira"></span>
            <span class="cartao-preview__numero" id="cartao-numero-visor">•••• •••• •••• ••••</span>
            <div class="cartao-preview__rodape">
              <span class="cartao-preview__titular" id="cartao-titular-visor">NOME DO TITULAR</span>
              <span class="cartao-preview__validade" id="cartao-validade-visor">MM/AA</span>
            </div>
          </div>

          <form method="POST" action="{{ route('orders.pagamento.processar', $pedido) }}" class="cartao-form" id="form-cartao">
            @csrf

            <div class="field">
              <label for="numero">Número do cartão</label>
              <input type="text" id="numero" name="numero" inputmode="numeric" autocomplete="cc-number"
                     placeholder="0000 0000 0000 0000" maxlength="23" value="{{ old('numero') }}" required>
            </div>

            <div class="field">
              <label for="titular">Nome impresso no cartão</label>
              <input type="text" id="titular" name="titular" autocomplete="cc-name"
                     placeholder="Como está no cartão" maxlength="120" value="{{ old('titular') }}" required>
            </div>

            <div class="cartao-form__linha">
              <div class="field">
                <label for="validade">Validade</label>
                <input type="text" id="validade" name="validade" inputmode="numeric" autocomplete="cc-exp"
                       placeholder="MM/AA" maxlength="5" value="{{ old('validade') }}" required>
              </div>
              <div class="field">
                <label for="cvv">Código de segurança</label>
                <input type="text" id="cvv" name="cvv" inputmode="numeric" autocomplete="cc-csc"
                       placeholder="CVV" maxlength="4" value="{{ old('cvv') }}" required>
              </div>
            </div>

            <div class="field">
              <label for="parcelas">Parcelamento</label>
              <select id="parcelas" name="parcelas" required>
                @foreach ($parcelasDisponiveis as $opcao)
                  <option value="{{ $opcao['parcelas'] }}" @selected(old('parcelas') == $opcao['parcelas'])>
                    {{ $opcao['rotulo'] }}
                  </option>
                @endforeach
              </select>
            </div>

            <button type="submit" class="btn btn-primary btn-block" id="btn-pagar-cartao">
              Pagar R$ {{ number_format($cobranca['valor'], 2, ',', '.') }}
            </button>
          </form>
        </div>
      @endif
    </div>

    {{-- ================= RESUMO ================= --}}
    <aside class="pagamento-resumo">
      <h2 class="cart-summary__title">Resumo</h2>

      <div class="cart-summary__total">
        <span>Subtotal</span>
        <span>R$ {{ number_format($pedido->total, 2, ',', '.') }}</span>
      </div>
      <div class="cart-summary__total">
        <span>Frete</span>
        <span>R$ {{ number_format($pedido->valor_frete ?? 0, 2, ',', '.') }}</span>
      </div>
      <div class="cart-summary__total cart-summary__total--destaque">
        <span>Total</span>
        <span>R$ {{ number_format($cobranca['valor'], 2, ',', '.') }}</span>
      </div>

      <div class="pagamento-resumo__meta">
        <span class="pagamento-resumo__rotulo">Código da cobrança</span>
        <span class="pagamento-resumo__valor">{{ $cobranca['codigo'] }}</span>
      </div>

      @if ($forma !== 'cartao')
        <form method="POST" action="{{ route('orders.pagamento.processar', $pedido) }}" id="form-confirmar-pagamento">
          @csrf
          <button type="submit" class="btn btn-primary btn-block" id="btn-ja-paguei">
            Já fiz o pagamento
          </button>
        </form>
        <p class="pagamento-resumo__aviso">
          Confirmamos com o banco antes de liberar o pedido para a loja.
        </p>
      @endif

      <a href="{{ route('orders.tracking') }}" class="link-btn pagamento-resumo__voltar">Pagar depois</a>

      {{-- SIMULACAO: o aviso sai junto com o GatewayDePagamentoSimulado --}}
      <p class="pagamento-resumo__simulado">
        Ambiente de demonstração — nenhuma cobrança real é feita.
      </p>
    </aside>
  </div>
</section>

<script>
  registerPageInit(function () {
    var raiz = document.querySelector('.pagamento-layout');
    if (!raiz) return;

    // ---- copiar codigo (pix e boleto)
    raiz.querySelectorAll('[data-copiar]').forEach(function (botao) {
      botao.addEventListener('click', function () {
        var alvo = document.querySelector(botao.dataset.copiar);
        if (!alvo) return;

        var texto = alvo.textContent.trim();
        var rotuloOriginal = botao.textContent;

        function avisar() {
          botao.textContent = 'Copiado!';
          setTimeout(function () { botao.textContent = rotuloOriginal; }, 2000);
        }

        // clipboard.writeText so existe em https (ou localhost); em http o
        // fallback do textarea escondido e o unico caminho que funciona
        if (navigator.clipboard && window.isSecureContext) {
          navigator.clipboard.writeText(texto).then(avisar, function () { copiarPorTextarea(texto, avisar); });
        } else {
          copiarPorTextarea(texto, avisar);
        }
      });
    });

    function copiarPorTextarea(texto, aoCopiar) {
      var area = document.createElement('textarea');
      area.value = texto;
      area.setAttribute('readonly', '');
      area.style.position = 'fixed';
      area.style.opacity = '0';
      document.body.appendChild(area);
      area.select();
      try { document.execCommand('copy'); aoCopiar(); } catch (e) { /* navegador bloqueou */ }
      document.body.removeChild(area);
    }

    // ---- formulario do cartao
    var numero = document.getElementById('numero');
    if (!numero) return;

    var visorNumero = document.getElementById('cartao-numero-visor');
    var visorTitular = document.getElementById('cartao-titular-visor');
    var visorValidade = document.getElementById('cartao-validade-visor');
    var visorBandeira = document.getElementById('cartao-bandeira');

    function bandeiraDe(digitos) {
      if (/^(4011|4312|4389|4514|4573|6362|6363|5067|5090)/.test(digitos)) return 'Elo';
      if (/^3[47]/.test(digitos)) return 'American Express';
      if (/^(5[1-5]|2[2-7])/.test(digitos)) return 'Mastercard';
      if (/^4/.test(digitos)) return 'Visa';
      if (/^(606282|3841)/.test(digitos)) return 'Hipercard';
      return '';
    }

    // completa com bolinhas ate 16 e agrupa de 4 em 4, para o visor nunca
    // "encolher" enquanto o cliente digita
    function visorDe(digitos) {
      var completo = digitos + '•'.repeat(Math.max(0, 16 - digitos.length));
      return completo.replace(/(.{4})(?=.)/g, '$1 ').trim();
    }

    numero.addEventListener('input', function () {
      var digitos = numero.value.replace(/\D/g, '').slice(0, 19);
      numero.value = digitos.replace(/(\d{4})(?=\d)/g, '$1 ').trim();

      visorNumero.textContent = visorDe(digitos);
      visorBandeira.textContent = bandeiraDe(digitos);
    });

    document.getElementById('titular').addEventListener('input', function (e) {
      visorTitular.textContent = e.target.value.toUpperCase() || 'NOME DO TITULAR';
    });

    var validade = document.getElementById('validade');
    validade.addEventListener('input', function () {
      var digitos = validade.value.replace(/\D/g, '').slice(0, 4);
      validade.value = digitos.length > 2 ? digitos.slice(0, 2) + '/' + digitos.slice(2) : digitos;
      visorValidade.textContent = validade.value || 'MM/AA';
    });

    document.getElementById('cvv').addEventListener('input', function (e) {
      e.target.value = e.target.value.replace(/\D/g, '').slice(0, 4);
    });
  });
</script>
@endsection
