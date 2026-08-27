{{--
  Espera do pagamento: anel que drena junto com o cronometro, e o botao do
  cliente avisar que pagou. So aparece em pix e boleto -- cartao autoriza na
  hora e nao tem o que esperar.

  A expiracao e decidida pelo servidor ($segundosRestantes vem da cobranca);
  o cronometro daqui so desenha a contagem. Mexer no relogio do navegador
  muda o desenho, nao a validade.
--}}
<div class="aguardando" id="aguardando" data-segundos="{{ $segundosRestantes }}">
  <div class="aguardando__visual">
    <svg class="aguardando__anel" viewBox="0 0 120 120" aria-hidden="true">
      <circle class="aguardando__trilha" cx="60" cy="60" r="52"></circle>
      {{-- o stroke-dashoffset e animado pelo JS: 327 = 2*pi*52 --}}
      <circle class="aguardando__progresso" id="aguardando-progresso" cx="60" cy="60" r="52"
              stroke-dasharray="327" stroke-dashoffset="0"></circle>
    </svg>

    <div class="aguardando__centro">
      <span class="aguardando__pulso" id="aguardando-pulso"></span>
      <span class="aguardando__relogio" id="aguardando-relogio">--:--</span>
    </div>
  </div>

  <div class="aguardando__texto">
    <span class="aguardando__titulo" id="aguardando-titulo">Aguardando pagamento</span>
    <p class="aguardando__ajuda" id="aguardando-ajuda">
      Assim que o banco identificar o pagamento, o pedido segue sozinho para a loja.
    </p>

    @if (session('aguardando'))
      <p class="aguardando__alerta">{{ session('aguardando') }}</p>
    @endif

    <form method="POST" action="{{ route('orders.pagamento.processar', $pedido) }}" id="form-ja-paguei">
      @csrf
      <button type="submit" class="btn btn-primary btn-block" id="btn-ja-paguei">
        Já fiz o pagamento
      </button>
    </form>

    {{-- so aparece quando o cronometro zera: recarregar a tela faz o servidor
         emitir uma cobranca nova, com codigo e janela novos --}}
    <a href="{{ route('orders.pagamento', $pedido) }}" class="btn btn-outline btn-block aguardando__renovar"
       id="btn-renovar" hidden>
      Gerar novo código
    </a>
  </div>
</div>
