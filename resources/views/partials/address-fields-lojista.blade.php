<div class="field">
  <label for="cep">CEP</label>
  <input type="text" id="cep" name="cep" inputmode="numeric" maxlength="9" placeholder="00000-000" autocomplete="postal-code" value="{{ old('cep') }}" required>
  <span class="field__hint" id="cep-hint"></span>
</div>

<div class="field-row">
  <div class="field field--grow">
    <label for="rua">Rua</label>
    <input type="text" id="rua" name="rua" autocomplete="address-line1" value="{{ old('rua') }}" required>
  </div>
  <div class="field field--small">
    <label for="numero">Número</label>
    <input type="text" id="numero" name="numero" autocomplete="off" value="{{ old('numero') }}" required>
  </div>
</div>

<div class="field">
  <label for="complemento">Complemento (opcional)</label>
  <input type="text" id="complemento" name="complemento" autocomplete="address-line2" placeholder="Apto, bloco, referência..." value="{{ old('complemento') }}">
</div>

<div class="field">
  <label for="bairro">Bairro</label>
  <input type="text" id="bairro" name="bairro" autocomplete="address-level3" value="{{ old('bairro') }}" required>
</div>

<div class="field-row">
  <div class="field field--grow">
    <label for="cidade">Cidade</label>
    <input type="text" id="cidade" name="cidade" autocomplete="address-level2" value="{{ old('cidade') }}" required>
  </div>
  <div class="field field--small">
    <label for="uf">UF</label>
    <input type="text" id="uf" name="estado" maxlength="2" autocomplete="address-level1" style="text-transform: uppercase;" value="{{ old('estado') }}" required>
  </div>
</div>
