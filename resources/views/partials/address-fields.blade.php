@php
  $obrig = $obrigatorio ?? true;
  $enderecoAtual = $enderecoAtual ?? null;
  $exibirEnderecoAtual = ! old('endereco') && $enderecoAtual;
@endphp

@if ($exibirEnderecoAtual)
  <div class="field">
    <label>Endereço atual</label>
    <p class="product-form-card__hint" style="margin-top: 0;">{{ $enderecoAtual }}</p>
  </div>
@endif

<div class="field">
  <label for="cep">CEP</label>
  <input type="text" id="cep" inputmode="numeric" maxlength="9" placeholder="00000-000" autocomplete="postal-code" @if($obrig) required @endif>
  <span class="field__hint" id="cep-hint"></span>
</div>

<div class="field-row">
  <div class="field field--grow">
    <label for="rua">Rua</label>
    <input type="text" id="rua" autocomplete="address-line1" @if($obrig) required @endif>
  </div>
  <div class="field field--small">
    <label for="numero">Número</label>
    <input type="text" id="numero" autocomplete="off" @if($obrig) required @endif>
  </div>
</div>

<div class="field">
  <label for="complemento">Complemento (opcional)</label>
  <input type="text" id="complemento" autocomplete="address-line2" placeholder="Apto, bloco, referência...">
</div>

<div class="field">
  <label for="bairro">Bairro</label>
  <input type="text" id="bairro" autocomplete="address-level3" @if($obrig) required @endif>
</div>

<div class="field-row">
  <div class="field field--grow">
    <label for="cidade">Cidade</label>
    <input type="text" id="cidade" autocomplete="address-level2" @if($obrig) required @endif>
  </div>
  <div class="field field--small">
    <label for="uf">UF</label>
    <input type="text" id="uf" maxlength="2" autocomplete="address-level1" style="text-transform: uppercase;" @if($obrig) required @endif>
  </div>
</div>

<input type="hidden" id="endereco" name="endereco" value="{{ old('endereco', $enderecoAtual) }}">
