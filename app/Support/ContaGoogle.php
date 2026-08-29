<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Entrada pela conta Google, em OAuth 2.0 puro.
 *
 * Nao usa Socialite de proposito: o fluxo do Google e um redirect, uma troca
 * de codigo por token e uma leitura de perfil -- tres chamadas HTTP que o
 * Guzzle do proprio Laravel ja faz. Um pacote novo obrigaria um
 * `composer install` a cada deploy, e o Codespace deste projeto so puxa o
 * codigo (docker/auto-pull.sh): a loja subiria quebrada com "class not found".
 *
 * O que NAO ha aqui e simulacao. Login e a fronteira de confianca do sistema:
 * um "Google de mentira" que aceitasse qualquer e-mail seria uma porta aberta,
 * nao uma maquete. Sem as chaves configuradas o botao simplesmente nao existe.
 */
class ContaGoogle
{
    private const AUTORIZACAO = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN = 'https://oauth2.googleapis.com/token';

    private const PERFIL = 'https://www.googleapis.com/oauth2/v3/userinfo';

    /** Chave da sessao onde o state fica guardado entre o redirect e a volta. */
    public const CHAVE_STATE = 'google_oauth_state';

    /** Ha credenciais para conversar com o Google? */
    public static function configurado(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }

    /**
     * URL para onde mandar o navegador, com o state que volta na callback.
     *
     * O state e a defesa contra CSRF do OAuth: quem chega na callback sem o
     * mesmo valor que saiu daqui nao veio deste navegador.
     */
    public static function urlDeAutorizacao(string $state): string
    {
        return self::AUTORIZACAO.'?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            // pede a tela de escolha de conta: sem isto o Google entra direto
            // com a ultima sessao e quem tem duas contas nao consegue trocar
            'prompt' => 'select_account',
        ]);
    }

    /**
     * Troca o codigo da callback pelo perfil da pessoa.
     *
     * @return array{google_id: string, nome: string, email: string, email_verificado: bool}
     *
     * @throws RuntimeException quando o Google recusa o codigo ou nao devolve e-mail
     */
    public static function perfilDoCodigo(string $codigo): array
    {
        $token = Http::asForm()->timeout(15)->post(self::TOKEN, [
            'code' => $codigo,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => config('services.google.redirect'),
            'grant_type' => 'authorization_code',
        ]);

        if ($token->failed() || blank($token->json('access_token'))) {
            throw new RuntimeException('O Google não confirmou esse acesso.');
        }

        $perfil = Http::withToken($token->json('access_token'))->timeout(15)->get(self::PERFIL);

        if ($perfil->failed()) {
            throw new RuntimeException('Não foi possível ler os dados da sua conta Google.');
        }

        $email = $perfil->json('email');
        $sub = $perfil->json('sub');

        if (blank($email) || blank($sub)) {
            throw new RuntimeException('Sua conta Google não devolveu um e-mail utilizável.');
        }

        return [
            'google_id' => (string) $sub,
            'nome' => (string) ($perfil->json('name') ?: strstr($email, '@', true)),
            'email' => (string) $email,
            // o Google diz se ele mesmo ja verificou o e-mail; sem isso alguem
            // poderia reivindicar um endereco que nao controla
            'email_verificado' => (bool) $perfil->json('email_verified'),
        ];
    }
}
