<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class Cnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cnpj = preg_replace('/\D/', '', (string) $value);

        if (! self::hasValidCheckDigits($cnpj)) {
            $fail('O CNPJ informado não é válido.');

            return;
        }

        $situacao = self::consultarSituacao($cnpj);

        if ($situacao === null) {
            $fail('Não foi possível confirmar o CNPJ na Receita Federal agora. Tente novamente em instantes.');

            return;
        }

        if ($situacao === false) {
            $fail('CNPJ não encontrado na Receita Federal.');

            return;
        }

        if ($situacao !== 'ATIVA') {
            $fail('O CNPJ informado não está com situação cadastral ativa.');
        }
    }

    public static function hasValidCheckDigits(string $cnpj): bool
    {
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $calcularDigito = function (string $base, array $pesos): int {
            $soma = 0;
            foreach ($pesos as $indice => $peso) {
                $soma += (int) $base[$indice] * $peso;
            }
            $resto = $soma % 11;

            return $resto < 2 ? 0 : 11 - $resto;
        };

        $digito1 = $calcularDigito(substr($cnpj, 0, 12), [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
        if ((int) $cnpj[12] !== $digito1) {
            return false;
        }

        $digito2 = $calcularDigito(substr($cnpj, 0, 13), [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);

        return (int) $cnpj[13] === $digito2;
    }

    /**
     * @return string|false|null situação cadastral, false se não encontrado, null se a consulta falhou
     */
    private static function consultarSituacao(string $cnpj): string|false|null
    {
        try {
            $response = Http::timeout(6)->get("https://brasilapi.com.br/api/cnpj/v1/{$cnpj}");
        } catch (\Throwable) {
            return null;
        }

        if ($response->status() === 404) {
            return false;
        }

        if (! $response->successful()) {
            return null;
        }

        return $response->json('descricao_situacao_cadastral') ?? false;
    }
}
