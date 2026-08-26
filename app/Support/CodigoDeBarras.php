<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Codigo de barras Intercalado 2 de 5 (ITF) em SVG.
 *
 * E a simbologia que os boletos brasileiros usam de fato: 44 digitos, sem
 * digito verificador proprio (a conferencia esta nos DVs do proprio codigo).
 * "Intercalado" porque os digitos andam em pares -- o primeiro vira barras, o
 * segundo vira os espacos entre elas -- e por isso a quantidade de digitos
 * precisa ser par.
 *
 * Sem dependencia externa, pelo mesmo motivo do QrCode: o projeto nao tem
 * pacote de codigo de barras e a loja nao tem build step.
 */
class CodigoDeBarras
{
    /**
     * Largura de cada digito: false = estreito, true = largo.
     *
     * Sempre 5 elementos, dois largos e tres estreitos -- dai o "2 de 5".
     *
     * @var array<int, array<int, bool>>
     */
    private const DIGITOS = [
        0 => [false, false, true, true, false],
        1 => [true, false, false, false, true],
        2 => [false, true, false, false, true],
        3 => [true, true, false, false, false],
        4 => [false, false, true, false, true],
        5 => [true, false, true, false, false],
        6 => [false, true, true, false, false],
        7 => [false, false, false, true, true],
        8 => [true, false, false, true, false],
        9 => [false, true, false, true, false],
    ];

    /** Quantas vezes o elemento largo e mais grosso que o estreito. */
    private const PROPORCAO_LARGO = 3;

    /**
     * SVG do codigo de barras dos digitos informados.
     *
     * @param  int  $estreito  largura do elemento estreito, em px
     * @param  int  $altura  altura das barras, em px
     */
    public static function svg(string $digitos, int $estreito = 2, int $altura = 80): string
    {
        $elementos = self::elementos($digitos);

        $largura = 0;

        foreach ($elementos as [, $largo]) {
            $largura += $largo ? $estreito * self::PROPORCAO_LARGO : $estreito;
        }

        $caminho = '';
        $x = 0;

        foreach ($elementos as [$barra, $largo]) {
            $espessura = $largo ? $estreito * self::PROPORCAO_LARGO : $estreito;

            if ($barra) {
                $caminho .= 'M'.$x.' 0h'.$espessura.'v'.$altura.'h-'.$espessura.'z';
            }

            $x += $espessura;
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$largura.'" height="'.$altura.'" '
            .'viewBox="0 0 '.$largura.' '.$altura.'" preserveAspectRatio="none" '
            .'shape-rendering="crispEdges" role="img" aria-label="Código de barras do boleto">'
            .'<rect width="'.$largura.'" height="'.$altura.'" fill="#ffffff"/>'
            .'<path d="'.$caminho.'" fill="#000000"/>'
            .'</svg>';
    }

    /**
     * Sequencia de elementos: [e barra?, e largo?].
     *
     * @return array<int, array{bool, bool}>
     */
    public static function elementos(string $digitos): array
    {
        if (! preg_match('/^\d+$/', $digitos)) {
            throw new InvalidArgumentException('O codigo de barras ITF aceita apenas digitos.');
        }

        if (strlen($digitos) % 2 !== 0) {
            throw new InvalidArgumentException('O ITF precisa de uma quantidade par de digitos.');
        }

        // inicio: barra-espaco-barra-espaco, todos estreitos
        $elementos = [[true, false], [false, false], [true, false], [false, false]];

        foreach (str_split($digitos, 2) as $par) {
            $barras = self::DIGITOS[(int) $par[0]];
            $espacos = self::DIGITOS[(int) $par[1]];

            // o par vira 10 elementos alternados: barra do primeiro digito,
            // espaco do segundo, barra do primeiro, espaco do segundo...
            for ($i = 0; $i < 5; $i++) {
                $elementos[] = [true, $barras[$i]];
                $elementos[] = [false, $espacos[$i]];
            }
        }

        // fim: barra larga, espaco estreito, barra estreita
        $elementos[] = [true, true];
        $elementos[] = [false, false];
        $elementos[] = [true, false];

        return $elementos;
    }
}
