<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida o formato/tamanho da Inscrição Estadual para todos os estados e,
 * para os estados listados em CHECKSUM_STATES, também o dígito verificador
 * oficial. Para os demais estados (algoritmo pouco documentado ou com regras
 * especiais por faixa), só o tamanho numérico é validado.
 */
class InscricaoEstadual implements DataAwareRule, ValidationRule
{
    private const LENGTHS = [
        'AC' => 13, 'AL' => 9, 'AP' => 9, 'AM' => 9, 'BA' => 8,
        'CE' => 9, 'DF' => 13, 'ES' => 9, 'GO' => 9, 'MA' => 9,
        'MT' => 11, 'MS' => 9, 'MG' => 13, 'PA' => 9, 'PB' => 9,
        'PE' => 9, 'PI' => 9, 'PR' => 10, 'RJ' => 8, 'RN' => 9,
        'RO' => 14, 'RR' => 9, 'RS' => 10, 'SC' => 9, 'SP' => 12,
        'SE' => 9, 'TO' => 9,
    ];

    private const CHECKSUM_STATES = ['SP', 'RJ', 'PR', 'RS', 'SC', 'ES', 'MS', 'MT', 'CE'];

    private array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $uf = strtoupper((string) ($this->data['estado'] ?? ''));
        $ie = preg_replace('/\D/', '', (string) $value);

        if (! isset(self::LENGTHS[$uf])) {
            $fail('Estado inválido para validação da inscrição estadual.');

            return;
        }

        if (strlen($ie) !== self::LENGTHS[$uf]) {
            $fail("A inscrição estadual deve ter ".self::LENGTHS[$uf]." dígitos para o estado {$uf}.");

            return;
        }

        if (in_array($uf, self::CHECKSUM_STATES, true) && ! self::checksumValido($uf, $ie)) {
            $fail('A inscrição estadual informada não é válida para o estado '.$uf.'.');
        }
    }

    private static function checksumValido(string $uf, string $ie): bool
    {
        return match ($uf) {
            'SP' => self::sp($ie),
            'RJ' => self::mod11UnicoDigito($ie, 7, [2, 7, 6, 5, 4, 3, 2]),
            'CE', 'SC', 'ES', 'MS' => self::mod11UnicoDigito($ie, 8, [9, 8, 7, 6, 5, 4, 3, 2]),
            'MT' => self::mod11UnicoDigito($ie, 10, [3, 2, 9, 8, 7, 6, 5, 4, 3, 2]),
            'RS' => self::mod11UnicoDigito($ie, 9, [2, 9, 8, 7, 6, 5, 4, 3, 2]),
            'PR' => self::pr($ie),
            default => true,
        };
    }

    private static function mod11UnicoDigito(string $ie, int $baseLen, array $pesos): bool
    {
        $soma = 0;
        for ($i = 0; $i < $baseLen; $i++) {
            $soma += (int) $ie[$i] * $pesos[$i];
        }
        $resto = $soma % 11;
        $digito = $resto < 2 ? 0 : 11 - $resto;

        return (int) $ie[$baseLen] === $digito;
    }

    private static function sp(string $ie): bool
    {
        $pesos1 = [1, 3, 4, 5, 6, 7, 8, 10];
        $soma1 = 0;
        for ($i = 0; $i < 8; $i++) {
            $soma1 += (int) $ie[$i] * $pesos1[$i];
        }
        $d1 = $soma1 % 11;
        if ($d1 >= 10) {
            $d1 = 0;
        }
        if ((int) $ie[8] !== $d1) {
            return false;
        }

        $pesos2 = [3, 2, 10, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma2 = 0;
        for ($i = 0; $i < 11; $i++) {
            $soma2 += (int) $ie[$i] * $pesos2[$i];
        }
        $d2 = $soma2 % 11;
        if ($d2 >= 10) {
            $d2 = 0;
        }

        return (int) $ie[11] === $d2;
    }

    private static function pr(string $ie): bool
    {
        $pesos1 = [3, 2, 7, 6, 5, 4, 3, 2];
        $soma1 = 0;
        for ($i = 0; $i < 8; $i++) {
            $soma1 += (int) $ie[$i] * $pesos1[$i];
        }
        $resto1 = $soma1 % 11;
        $d1 = $resto1 < 2 ? 0 : 11 - $resto1;
        if ((int) $ie[8] !== $d1) {
            return false;
        }

        $pesos2 = [4, 3, 2, 7, 6, 5, 4, 3, 2];
        $soma2 = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma2 += (int) $ie[$i] * $pesos2[$i];
        }
        $resto2 = $soma2 % 11;
        $d2 = $resto2 < 2 ? 0 : 11 - $resto2;

        return (int) $ie[9] === $d2;
    }
}
