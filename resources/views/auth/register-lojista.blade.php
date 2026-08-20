@extends('layouts.base')

@section('title', 'Cadastro de Lojista · HR Moda Online')

@section('content')
<div class="auth-card product-form-card">
  <h1 class="auth-card__title">Cadastro de Lojista</h1>
  <p class="auth-card__subtitle">Preencha os dados da sua loja e envie a documentação para começar a vender.</p>

  @if ($errors->any())
    <div class="form-status form-status--error">
      @foreach ($errors->all() as $error)
        <p>{{ $error }}</p>
      @endforeach
    </div>
  @endif

  <form method="POST" action="{{ route('register.lojista') }}" enctype="multipart/form-data">
    @csrf

    <h2 class="form-section-title">Dados de acesso</h2>

    <div class="field">
      <label for="name">Nome do responsável</label>
      <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus>
    </div>

    <div class="field">
      <label for="email">E-mail</label>
      <input type="email" id="email" name="email" value="{{ old('email') }}" required>
    </div>

    <div class="field">
      <label for="telefone">Telefone</label>
      <input type="text" id="telefone" name="telefone" inputmode="numeric" placeholder="(00) 00000-0000" value="{{ old('telefone') }}" required>
    </div>

    <div class="field">
      <label for="password">Senha</label>
      <input type="password" id="password" name="password" required>
    </div>

    <div class="field">
      <label for="password_confirmation">Confirmar senha</label>
      <input type="password" id="password_confirmation" name="password_confirmation" required>
    </div>

    <h2 class="form-section-title">Dados da loja</h2>

    <div class="field">
      <label for="nome_fantasia">Nome fantasia</label>
      <input type="text" id="nome_fantasia" name="nome_fantasia" value="{{ old('nome_fantasia') }}" required>
    </div>

    <div class="field">
      <label>Tipo de pessoa</label>
      <div class="radio-group radio-group--inline">
        <label class="radio">
          <input type="radio" name="tipo_pessoa" value="fisica" data-toggle-tipo-pessoa {{ old('tipo_pessoa', 'fisica') === 'fisica' ? 'checked' : '' }}>
          Pessoa física
        </label>
        <label class="radio">
          <input type="radio" name="tipo_pessoa" value="juridica" data-toggle-tipo-pessoa {{ old('tipo_pessoa') === 'juridica' ? 'checked' : '' }}>
          Pessoa jurídica
        </label>
      </div>
    </div>

    <div class="field" id="bloco-cpf">
      <label for="cpf">CPF</label>
      <input type="text" id="cpf" name="cpf" inputmode="numeric" placeholder="000.000.000-00" value="{{ old('cpf') }}">
      <span class="field__hint" id="cpf-hint"></span>
    </div>

    <div class="field" id="bloco-cnpj" style="display: none;">
      <label for="cnpj">CNPJ</label>
      <input type="text" id="cnpj" name="cnpj" inputmode="numeric" placeholder="00.000.000/0000-00" value="{{ old('cnpj') }}">
      <span class="field__hint" id="cnpj-hint"></span>
    </div>

    <div class="field" id="bloco-razao-social" style="display: none;">
      <label for="razao_social">Razão social</label>
      <input type="text" id="razao_social" name="razao_social" value="{{ old('razao_social') }}">
    </div>

    <div class="field" id="bloco-contrato-social" style="display: none;">
      <label for="contrato_social_mei">Contrato social ou comprovante de MEI</label>
      <input type="file" id="contrato_social_mei" name="contrato_social_mei" accept=".pdf,.jpg,.jpeg,.png">
    </div>

    <div class="field">
      <label class="checkbox">
        <input type="checkbox" id="ie_isento" name="ie_isento" value="1" {{ old('ie_isento') ? 'checked' : '' }}>
        <span>Sou isento de Inscrição Estadual</span>
      </label>
    </div>

    <div class="field" id="bloco-ie">
      <label for="inscricao_estadual">Inscrição Estadual</label>
      <input type="text" id="inscricao_estadual" name="inscricao_estadual" value="{{ old('inscricao_estadual') }}">
      <span class="field__hint">Informe o número apenas com dígitos, sem pontos ou traços.</span>
    </div>

    <div class="field">
      <label for="logotipo">Logotipo da loja</label>
      <input type="file" id="logotipo" name="logotipo" accept=".jpg,.jpeg,.png,.webp" required>
    </div>

    <div class="field">
      <label for="descricao_loja">Descrição da loja</label>
      <textarea id="descricao_loja" name="descricao_loja" placeholder="Conte um pouco sobre sua loja...">{{ old('descricao_loja') }}</textarea>
    </div>

    <h2 class="form-section-title">Endereço de expedição</h2>
    <p class="product-form-card__hint" style="margin-top: 0;">Endereço de onde os pedidos serão despachados.</p>

    @include('partials.address-fields-lojista')

    <h2 class="form-section-title">Envio e política</h2>

    <div class="field">
      <label for="prazo_expedicao_dias_uteis">Prazo de expedição (dias úteis)</label>
      <input type="number" id="prazo_expedicao_dias_uteis" name="prazo_expedicao_dias_uteis" min="1" max="365" value="{{ old('prazo_expedicao_dias_uteis') }}" required>
    </div>

    <div class="field">
      <label for="politica_troca_devolucao">Política de troca e devolução</label>
      <textarea id="politica_troca_devolucao" name="politica_troca_devolucao" placeholder="Descreva as condições de troca e devolução praticadas pela sua loja...">{{ old('politica_troca_devolucao') }}</textarea>
    </div>

    <h2 class="form-section-title">Documentação (KYC)</h2>
    <p class="product-form-card__hint" style="margin-top: 0;">Seus documentos são usados apenas para validação do cadastro e não ficam públicos no site.</p>

    <div class="field">
      <label for="documento_identidade">Documento de identidade do responsável (RG, CNH ou similar)</label>
      <input type="file" id="documento_identidade" name="documento_identidade" accept=".pdf,.jpg,.jpeg,.png" required>
    </div>

    <div class="field">
      <label for="selfie_documento">Selfie segurando o documento de identidade</label>
      <input type="file" id="selfie_documento" name="selfie_documento" accept=".jpg,.jpeg,.png" required>
    </div>

    <div class="field">
      <label for="comprovante_endereco">Comprovante de endereço</label>
      <input type="file" id="comprovante_endereco" name="comprovante_endereco" accept=".pdf,.jpg,.jpeg,.png" required>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Enviar cadastro</button>
  </form>

  <p class="auth-card__footer">Já tem conta? <a href="{{ route('login') }}">Entrar</a></p>
</div>
@endsection
