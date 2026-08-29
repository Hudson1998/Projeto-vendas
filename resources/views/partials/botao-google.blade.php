@php
  $googleConfigurado = App\Support\ContaGoogle::configurado();

  // Em producao o botao so existe quando ha credenciais -- um botao que nao
  // funciona e pior do que botao nenhum. Em desenvolvimento ele aparece mesmo
  // sem chave, porque senao a funcionalidade fica invisivel para quem esta
  // montando a loja e nao ha como descobrir o que falta.
  $mostrarGoogle = $googleConfigurado || app()->environment('local');
  $separador = $separador ?? 'ou preencha seus dados';
@endphp

@if ($mostrarGoogle)
  <a href="{{ route('google.redirect') }}" class="btn-google @if(! $googleConfigurado) is-pendente @endif">
    <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
      <path fill="#4285F4" d="M45.1 24.5c0-1.6-.1-3.2-.4-4.7H24v8.9h11.8a10 10 0 0 1-4.4 6.6v5.5h7.1c4.2-3.8 6.6-9.5 6.6-16.3z"></path>
      <path fill="#34A853" d="M24 46c6 0 11-2 14.6-5.2l-7.1-5.5a13.6 13.6 0 0 1-20.3-7.1H3.9v5.7A22 22 0 0 0 24 46z"></path>
      <path fill="#FBBC05" d="M11.2 28.2a13.2 13.2 0 0 1 0-8.4v-5.7H3.9a22 22 0 0 0 0 19.8l7.3-5.7z"></path>
      <path fill="#EA4335" d="M24 10.3c3.3 0 6.2 1.1 8.5 3.3l6.3-6.3A21 21 0 0 0 24 2 22 22 0 0 0 3.9 14.1l7.3 5.7A13.1 13.1 0 0 1 24 10.3z"></path>
    </svg>
    Continuar com o Google
  </a>

  @unless ($googleConfigurado)
    <p class="google-pendente">
      Falta configurar <code>GOOGLE_CLIENT_ID</code> e <code>GOOGLE_CLIENT_SECRET</code>
      no <code>.env</code>. Este aviso só aparece em desenvolvimento.
    </p>
  @endunless

  <div class="auth-separador"><span>{{ $separador }}</span></div>
@endif
