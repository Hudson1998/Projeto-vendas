<?php

namespace App\Support;

class NumberAbbreviator
{
    public static function abreviar(int|float $numero): string
    {
        [$valor, $sufixo] = self::escalar((float) $numero);

        if ($sufixo === '') {
            return (string) (int) $numero;
        }

        return self::formatarComSufixo($valor).$sufixo;
    }

    public static function abreviarMoeda(int|float $valor): string
    {
        $valor = (float) $valor;
        [$escalado, $sufixo] = self::escalar($valor);

        if ($sufixo === '') {
            return 'R$ '.number_format($valor, 2, ',', '.');
        }

        return 'R$ '.self::formatarComSufixo($escalado).$sufixo;
    }

    /**
     * @return array{0: float, 1: string}
     */
    private static function escalar(float $numero): array
    {
        if ($numero >= 1_000_000) {
            return [$numero / 1_000_000, 'M'];
        }

        if ($numero >= 1_000) {
            if (round($numero / 1_000, 1) >= 1000) {
                return [$numero / 1_000_000, 'M'];
            }

            return [$numero / 1_000, 'K'];
        }

        return [$numero, ''];
    }

    private static function formatarComSufixo(float $valor): string
    {
        $arredondado = round($valor, 1);
        $formatado = number_format($arredondado, 1, ',', '');

        return rtrim(rtrim($formatado, '0'), ',');
    }
}
