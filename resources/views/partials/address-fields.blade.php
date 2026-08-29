@php
  $obrig = $obrigatorio ?? true;
  $enderecoAtual = $enderecoAtual ?? null;
  $exibirEnderecoAtual = ! old('endereco') && $enderecoAtual;

  // Estes campos nao tem name: o cep.js junta todos no hidden #endereco, que e
  // o unico que chega ao servidor. Entao o "obrigatorio" deles e verificado no
  // navegador -- data-obrigatorio carrega a mensagem que o validador mostra.
  $exigir = fn (string $mensagem) => $obrig ? 'data-obrigatorio="'.e($mensagem).'"' : '';
@endphp

@if ($exibirEnderecoAtual)
  <div class="field">
    <label>Endereço atual</label>
    <p class="product-form-card__hint" style="margin-top: 0;">{{ $enderecoAtual }}</p>
  </div>
@endif

@if ($obrig)
  @error('endereco')
    <div class="form-status form-status--error" role="alert"><p>{{ $message }}</p></div>
  @enderror
@endif

<div class="field">
  <label for="cep">CEP</label>
  <input type="text" id="cep" inputmode="numeric" maxlength="9" placeholder="00000-000" autocomplete="postal-code" {!! $exigir('Preencha o CEP.') !!}>
  <span class="field__hint" id="cep-hint"></span>
  <span class="field__erro" hidden></span>
</div>

<div class="field-row">
  <div class="field field--grow">
    <label for="rua">Rua</label>
    <input type="text" id="rua" autocomplete="address-line1" {!! $exigir('Preencha a rua.') !!}>
    <span class="field__erro" hidden></span>
  </div>
  <div class="field field--small">
    <label for="numero">Número</label>
    <input type="text" id="numero" autocomplete="off" {!! $exigir('Informe o número.') !!}>
    <span class="field__erro" hidden></span>
  </div>
</div>

<div class="field">
  <label for="complemento">Complemento (opcional)</label>
  <input type="text" id="complemento" autocomplete="address-line2" placeholder="Apto, bloco, referência...">
</div>

<div class="field">
  <label for="bairro">Bairro</label>
  <input type="text" id="bairro" autocomplete="address-level3" {!! $exigir('Preencha o bairro.') !!}>
  <span class="field__erro" hidden></span>
</div>

<div class="field-row">
  <div class="field field--grow">
    <label for="cidade">Cidade</label>
    <input type="text" id="cidade" autocomplete="address-level2" {!! $exigir('Preencha a cidade.') !!}>
    <span class="field__erro" hidden></span>
  </div>
  <div class="field field--small">
    <label for="uf">UF</label>
    <input type="text" id="uf" maxlength="2" autocomplete="address-level1" style="text-transform: uppercase;" {!! $exigir('UF.') !!}>
    <span class="field__erro" hidden></span>
  </div>
</div>

<input type="hidden" id="endereco" name="endereco" value="{{ old('endereco', $enderecoAtual) }}">
