<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Gerador de QR Code em SVG, sem dependencia externa.
 *
 * O projeto nao tem pacote de QR no composer e a loja nao tem build step: um
 * QR desenhado "de mentira" nao passaria em nenhum leitor, e a tela do pix
 * ficaria com um enfeite no lugar do codigo. Entao o codigo e gerado de
 * verdade aqui -- modo byte, correcao de erro nivel M, versoes 1 a 20, que
 * cobrem com folga os ~150 caracteres de um payload pix.
 *
 * Sobrevive a troca do gateway simulado: pix de verdade tambem precisa
 * transformar o BR Code em imagem, e quem chama so entrega a string.
 */
class QrCode
{
    /** Modo byte: 8 bits por caractere. */
    private const MODO_BYTE = 0b0100;

    /**
     * Estrutura de blocos do nivel de correcao M, versoes 1 a 20.
     *
     * [total de codewords, codewords de EC por bloco,
     *  blocos do grupo 1, dados por bloco do grupo 1,
     *  blocos do grupo 2, dados por bloco do grupo 2]
     *
     * @var array<int, array{int, int, int, int, int, int}>
     */
    private const BLOCOS = [
        1 => [26, 10, 1, 16, 0, 0],
        2 => [44, 16, 1, 28, 0, 0],
        3 => [70, 26, 1, 44, 0, 0],
        4 => [100, 18, 2, 32, 0, 0],
        5 => [134, 24, 2, 43, 0, 0],
        6 => [172, 16, 4, 27, 0, 0],
        7 => [196, 18, 4, 31, 0, 0],
        8 => [242, 22, 2, 38, 2, 39],
        9 => [292, 22, 3, 36, 2, 37],
        10 => [346, 26, 4, 43, 1, 44],
        11 => [404, 30, 1, 50, 4, 51],
        12 => [466, 22, 6, 36, 2, 37],
        13 => [532, 22, 8, 37, 1, 38],
        14 => [581, 24, 4, 40, 5, 41],
        15 => [655, 24, 5, 41, 5, 42],
        16 => [733, 28, 7, 45, 3, 46],
        17 => [815, 28, 10, 46, 1, 47],
        18 => [901, 26, 9, 43, 4, 44],
        19 => [991, 26, 3, 44, 11, 45],
        20 => [1085, 26, 3, 41, 13, 42],
    ];

    /**
     * Centros dos padroes de alinhamento por versao.
     *
     * @var array<int, array<int, int>>
     */
    private const ALINHAMENTO = [
        1 => [], 2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30],
        6 => [6, 34], 7 => [6, 22, 38], 8 => [6, 24, 42], 9 => [6, 26, 46],
        10 => [6, 28, 50], 11 => [6, 30, 54], 12 => [6, 32, 58], 13 => [6, 34, 62],
        14 => [6, 26, 46, 66], 15 => [6, 26, 48, 70], 16 => [6, 26, 50, 74],
        17 => [6, 30, 54, 78], 18 => [6, 30, 56, 82], 19 => [6, 30, 58, 86],
        20 => [6, 34, 62, 90],
    ];

    /** Nivel M na numeracao do formato (nao e 1 nem 2 -- a ordem do padrao e L=01, M=00, Q=11, H=10). */
    private const NIVEL_M = 0b00;

    /** @var array<int, int> exponencial de GF(256) */
    private static array $exp = [];

    /** @var array<int, int> logaritmo de GF(256) */
    private static array $log = [];

    /**
     * SVG do QR Code do texto informado.
     *
     * @param  int  $modulo  lado de cada modulo, em px
     * @param  int  $margem  quiet zone, em modulos (o padrao exige 4)
     */
    public static function svg(string $texto, int $modulo = 6, int $margem = 4): string
    {
        $matriz = self::matriz($texto);
        $lado = count($matriz);
        $total = ($lado + $margem * 2) * $modulo;

        // um unico <path> com todos os modulos escuros: um <rect> por modulo
        // deixaria o SVG com milhares de nos e pesado no DOM
        $caminho = '';

        foreach ($matriz as $y => $linha) {
            foreach ($linha as $x => $escuro) {
                if ($escuro) {
                    $caminho .= 'M'.(($x + $margem) * $modulo).' '.(($y + $margem) * $modulo)
                        .'h'.$modulo.'v'.$modulo.'h-'.$modulo.'z';
                }
            }
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$total.'" height="'.$total.'" '
            .'viewBox="0 0 '.$total.' '.$total.'" shape-rendering="crispEdges" role="img" '
            .'aria-label="QR Code para pagamento via Pix">'
            .'<rect width="'.$total.'" height="'.$total.'" fill="#ffffff"/>'
            .'<path d="'.$caminho.'" fill="#000000"/>'
            .'</svg>';
    }

    /**
     * Matriz de modulos do QR: true = escuro.
     *
     * @return array<int, array<int, bool>>
     */
    public static function matriz(string $texto): array
    {
        self::prepararGaloisField();

        $versao = self::escolherVersao($texto);
        [, $ecPorBloco, $blocos1, $dados1, $blocos2, $dados2] = self::BLOCOS[$versao];

        $bits = self::montarBits($texto, $versao);
        $codewords = self::intercalar($bits, $ecPorBloco, $blocos1, $dados1, $blocos2, $dados2);

        $lado = 17 + $versao * 4;
        $reservado = self::matrizReservada($versao, $lado);
        $matriz = self::matrizBase($versao, $lado);

        self::posicionarDados($matriz, $reservado, $codewords, $lado);

        // escolhe a mascara que menos penaliza a leitura
        $melhor = null;
        $melhorPenalidade = PHP_INT_MAX;

        for ($mascara = 0; $mascara < 8; $mascara++) {
            $candidata = self::aplicarMascara($matriz, $reservado, $mascara, $lado);
            self::gravarFormato($candidata, $mascara, $lado);

            $penalidade = self::penalidade($candidata, $lado);

            if ($penalidade < $melhorPenalidade) {
                $melhorPenalidade = $penalidade;
                $melhor = $candidata;
            }
        }

        return $melhor;
    }

    /** Menor versao que comporta o texto no nivel M. */
    private static function escolherVersao(string $texto): int
    {
        $bytes = strlen($texto);

        foreach (self::BLOCOS as $versao => [, $ecPorBloco, $blocos1, $dados1, $blocos2, $dados2]) {
            $capacidade = $blocos1 * $dados1 + $blocos2 * $dados2;
            // 4 bits de modo + 8 ou 16 bits de contagem, arredondado para cima
            $cabecalho = (int) ceil((4 + ($versao < 10 ? 8 : 16)) / 8);

            if ($bytes + $cabecalho <= $capacidade) {
                return $versao;
            }
        }

        throw new InvalidArgumentException('Texto longo demais para um QR Code versao 20 nivel M.');
    }

    /** Bits de dados: modo, contagem, conteudo, terminador e preenchimento. */
    private static function montarBits(string $texto, int $versao): string
    {
        [, $ecPorBloco, $blocos1, $dados1, $blocos2, $dados2] = self::BLOCOS[$versao];
        $capacidade = ($blocos1 * $dados1 + $blocos2 * $dados2) * 8;

        $bits = sprintf('%04b', self::MODO_BYTE);
        $bits .= sprintf('%0'.($versao < 10 ? 8 : 16).'b', strlen($texto));

        foreach (str_split($texto) as $caractere) {
            $bits .= sprintf('%08b', ord($caractere));
        }

        // terminador: ate 4 zeros, o que couber
        $bits .= str_repeat('0', min(4, $capacidade - strlen($bits)));

        // completa o ultimo codeword
        if (strlen($bits) % 8 !== 0) {
            $bits .= str_repeat('0', 8 - strlen($bits) % 8);
        }

        // bytes de enchimento alternados, definidos pelo padrao
        $enchimento = ['11101100', '00010001'];
        $i = 0;

        while (strlen($bits) < $capacidade) {
            $bits .= $enchimento[$i++ % 2];
        }

        return $bits;
    }

    /**
     * Divide em blocos, calcula a correcao de erro e intercala tudo.
     *
     * @return array<int, int>
     */
    private static function intercalar(string $bits, int $ecPorBloco, int $blocos1, int $dados1, int $blocos2, int $dados2): array
    {
        $bytes = array_map('bindec', str_split($bits, 8));

        $blocosDados = [];
        $blocosEc = [];
        $posicao = 0;

        foreach ([[$blocos1, $dados1], [$blocos2, $dados2]] as [$quantidade, $tamanho]) {
            for ($b = 0; $b < $quantidade; $b++) {
                $bloco = array_slice($bytes, $posicao, $tamanho);
                $posicao += $tamanho;

                $blocosDados[] = $bloco;
                $blocosEc[] = self::reedSolomon($bloco, $ecPorBloco);
            }
        }

        $saida = [];

        // dados: primeiro byte de cada bloco, depois o segundo, e assim por diante
        for ($i = 0; $i < max($dados1, $dados2); $i++) {
            foreach ($blocosDados as $bloco) {
                if (isset($bloco[$i])) {
                    $saida[] = $bloco[$i];
                }
            }
        }

        for ($i = 0; $i < $ecPorBloco; $i++) {
            foreach ($blocosEc as $bloco) {
                if (isset($bloco[$i])) {
                    $saida[] = $bloco[$i];
                }
            }
        }

        return $saida;
    }

    /** Tabelas de exponencial e logaritmo do GF(256), polinomio 0x11d. */
    private static function prepararGaloisField(): void
    {
        if (self::$exp !== []) {
            return;
        }

        $valor = 1;

        for ($i = 0; $i < 256; $i++) {
            self::$exp[$i] = $valor;
            self::$log[$valor] = $i;

            $valor <<= 1;

            if ($valor & 0x100) {
                $valor ^= 0x11D;
            }
        }

        for ($i = 256; $i < 512; $i++) {
            self::$exp[$i] = self::$exp[$i - 255];
        }
    }

    private static function multiplicar(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) {
            return 0;
        }

        return self::$exp[(self::$log[$a] + self::$log[$b]) % 255];
    }

    /**
     * Codewords de correcao de erro de um bloco.
     *
     * @param  array<int, int>  $dados
     * @return array<int, int>
     */
    private static function reedSolomon(array $dados, int $quantidade): array
    {
        // gerador: (x - a^0)(x - a^1)...(x - a^(n-1))
        $gerador = [1];

        for ($i = 0; $i < $quantidade; $i++) {
            $novo = array_fill(0, count($gerador) + 1, 0);

            foreach ($gerador as $j => $coeficiente) {
                $novo[$j] ^= $coeficiente;
                $novo[$j + 1] ^= self::multiplicar($coeficiente, self::$exp[$i]);
            }

            $gerador = $novo;
        }

        $resto = array_merge($dados, array_fill(0, $quantidade, 0));

        for ($i = 0; $i < count($dados); $i++) {
            $fator = $resto[$i];

            if ($fator === 0) {
                continue;
            }

            foreach ($gerador as $j => $coeficiente) {
                $resto[$i + $j] ^= self::multiplicar($coeficiente, $fator);
            }
        }

        return array_slice($resto, count($dados));
    }

    /**
     * Matriz marcando o que e area funcional (nao recebe dados nem mascara).
     *
     * @return array<int, array<int, bool>>
     */
    private static function matrizReservada(int $versao, int $lado): array
    {
        $reservado = array_fill(0, $lado, array_fill(0, $lado, false));

        $marcar = function (int $topo, int $esquerda, int $altura, int $largura) use (&$reservado, $lado) {
            for ($y = $topo; $y < $topo + $altura; $y++) {
                for ($x = $esquerda; $x < $esquerda + $largura; $x++) {
                    if ($y >= 0 && $y < $lado && $x >= 0 && $x < $lado) {
                        $reservado[$y][$x] = true;
                    }
                }
            }
        };

        // localizadores + separadores + area do formato
        $marcar(0, 0, 9, 9);
        $marcar(0, $lado - 8, 9, 8);
        $marcar($lado - 8, 0, 8, 9);

        // linhas de tempo
        $marcar(6, 0, 1, $lado);
        $marcar(0, 6, $lado, 1);

        // alinhamento, exceto onde colidiria com os localizadores
        $centros = self::ALINHAMENTO[$versao];

        foreach ($centros as $cy) {
            foreach ($centros as $cx) {
                $noCanto = ($cx === 6 && $cy === 6)
                    || ($cx === 6 && $cy === $lado - 7)
                    || ($cx === $lado - 7 && $cy === 6);

                if (! $noCanto) {
                    $marcar($cy - 2, $cx - 2, 5, 5);
                }
            }
        }

        // informacao de versao (7 em diante)
        if ($versao >= 7) {
            $marcar($lado - 11, 0, 3, 6);
            $marcar(0, $lado - 11, 6, 3);
        }

        return $reservado;
    }

    /**
     * Matriz com os padroes fixos ja desenhados.
     *
     * @return array<int, array<int, bool>>
     */
    private static function matrizBase(int $versao, int $lado): array
    {
        $matriz = array_fill(0, $lado, array_fill(0, $lado, false));

        $localizador = function (int $topo, int $esquerda) use (&$matriz, $lado) {
            for ($y = -1; $y <= 7; $y++) {
                for ($x = -1; $x <= 7; $x++) {
                    $py = $topo + $y;
                    $px = $esquerda + $x;

                    if ($py < 0 || $py >= $lado || $px < 0 || $px >= $lado) {
                        continue;
                    }

                    $borda = $y === 0 || $y === 6 || $x === 0 || $x === 6;
                    $centro = $y >= 2 && $y <= 4 && $x >= 2 && $x <= 4;
                    $dentro = $y >= 0 && $y <= 6 && $x >= 0 && $x <= 6;

                    $matriz[$py][$px] = $dentro && ($borda || $centro);
                }
            }
        };

        $localizador(0, 0);
        $localizador(0, $lado - 7);
        $localizador($lado - 7, 0);

        // linhas de tempo: alterna a cada modulo
        for ($i = 8; $i < $lado - 8; $i++) {
            $matriz[6][$i] = $i % 2 === 0;
            $matriz[$i][6] = $i % 2 === 0;
        }

        // alinhamento
        $centros = self::ALINHAMENTO[$versao];

        foreach ($centros as $cy) {
            foreach ($centros as $cx) {
                $noCanto = ($cx === 6 && $cy === 6)
                    || ($cx === 6 && $cy === $lado - 7)
                    || ($cx === $lado - 7 && $cy === 6);

                if ($noCanto) {
                    continue;
                }

                for ($y = -2; $y <= 2; $y++) {
                    for ($x = -2; $x <= 2; $x++) {
                        $matriz[$cy + $y][$cx + $x] = abs($y) === 2 || abs($x) === 2 || ($y === 0 && $x === 0);
                    }
                }
            }
        }

        // modulo escuro fixo
        $matriz[$lado - 8][8] = true;

        if ($versao >= 7) {
            self::gravarVersao($matriz, $versao, $lado);
        }

        return $matriz;
    }

    /**
     * Percorre a matriz em ziguezague, da direita para a esquerda, gravando os bits.
     *
     * @param  array<int, array<int, bool>>  $matriz
     * @param  array<int, array<int, bool>>  $reservado
     * @param  array<int, int>  $codewords
     */
    private static function posicionarDados(array &$matriz, array $reservado, array $codewords, int $lado): void
    {
        $bits = '';

        foreach ($codewords as $codeword) {
            $bits .= sprintf('%08b', $codeword);
        }

        $indice = 0;
        $subindo = true;

        for ($direita = $lado - 1; $direita > 0; $direita -= 2) {
            // a coluna 6 e linha de tempo: o par de colunas pula para a esquerda
            if ($direita === 6) {
                $direita = 5;
            }

            for ($passo = 0; $passo < $lado; $passo++) {
                $y = $subindo ? $lado - 1 - $passo : $passo;

                foreach ([$direita, $direita - 1] as $x) {
                    if ($reservado[$y][$x]) {
                        continue;
                    }

                    $matriz[$y][$x] = isset($bits[$indice]) && $bits[$indice] === '1';
                    $indice++;
                }
            }

            $subindo = ! $subindo;
        }
    }

    /**
     * @param  array<int, array<int, bool>>  $matriz
     * @param  array<int, array<int, bool>>  $reservado
     * @return array<int, array<int, bool>>
     */
    private static function aplicarMascara(array $matriz, array $reservado, int $mascara, int $lado): array
    {
        for ($y = 0; $y < $lado; $y++) {
            for ($x = 0; $x < $lado; $x++) {
                if ($reservado[$y][$x]) {
                    continue;
                }

                $inverter = match ($mascara) {
                    0 => ($y + $x) % 2 === 0,
                    1 => $y % 2 === 0,
                    2 => $x % 3 === 0,
                    3 => ($y + $x) % 3 === 0,
                    4 => (intdiv($y, 2) + intdiv($x, 3)) % 2 === 0,
                    5 => ($y * $x) % 2 + ($y * $x) % 3 === 0,
                    6 => (($y * $x) % 2 + ($y * $x) % 3) % 2 === 0,
                    default => (($y + $x) % 2 + ($y * $x) % 3) % 2 === 0,
                };

                if ($inverter) {
                    $matriz[$y][$x] = ! $matriz[$y][$x];
                }
            }
        }

        return $matriz;
    }

    /**
     * Grava os 15 bits de formato (nivel de correcao + mascara), com BCH.
     *
     * @param  array<int, array<int, bool>>  $matriz
     */
    private static function gravarFormato(array &$matriz, int $mascara, int $lado): void
    {
        $dados = (self::NIVEL_M << 3) | $mascara;
        $resto = $dados << 10;

        for ($i = 4; $i >= 0; $i--) {
            if ($resto & (1 << ($i + 10))) {
                $resto ^= 0b10100110111 << $i;
            }
        }

        $formato = (($dados << 10) | $resto) ^ 0b101010000010010;

        // Atencao a orientacao: os bits menos significativos descem pela COLUNA
        // 8 e os mais significativos correm pela LINHA 8 -- nao o contrario.
        // Trocar linha por coluna aqui produz um QR de aparencia perfeita e
        // ilegivel em qualquer leitor, porque o decodificador le o nivel de
        // correcao e a mascara errados antes de chegar nos dados.
        for ($i = 0; $i < 15; $i++) {
            $bit = (bool) (($formato >> $i) & 1);

            // copia 1, em volta do localizador superior esquerdo:
            // bits 0-7 pela coluna 8 (de cima para baixo), 8-14 pela linha 8
            // (da direita para a esquerda), pulando as linhas de tempo
            if ($i < 6) {
                $matriz[$i][8] = $bit;
            } elseif ($i === 6) {
                $matriz[7][8] = $bit;
            } elseif ($i === 7) {
                $matriz[8][8] = $bit;
            } elseif ($i === 8) {
                $matriz[8][7] = $bit;
            } else {
                $matriz[8][14 - $i] = $bit;
            }

            // copia 2: bits 0-7 pela linha 8 ate a borda direita, 8-14 subindo
            // pela coluna 8 a partir do rodape. O modulo escuro fixo, em
            // (lado-8, 8), fica de fora dos dois trechos.
            if ($i < 8) {
                $matriz[8][$lado - 1 - $i] = $bit;
            } else {
                $matriz[$lado - 15 + $i][8] = $bit;
            }
        }
    }

    /**
     * Grava os 18 bits de versao (so a partir da versao 7), com BCH.
     *
     * @param  array<int, array<int, bool>>  $matriz
     */
    private static function gravarVersao(array &$matriz, int $versao, int $lado): void
    {
        $resto = $versao << 12;

        for ($i = 5; $i >= 0; $i--) {
            if ($resto & (1 << ($i + 12))) {
                $resto ^= 0b1111100100101 << $i;
            }
        }

        $informacao = ($versao << 12) | $resto;

        for ($i = 0; $i < 18; $i++) {
            $bit = (bool) (($informacao >> $i) & 1);

            $matriz[intdiv($i, 3)][$lado - 11 + $i % 3] = $bit;
            $matriz[$lado - 11 + $i % 3][intdiv($i, 3)] = $bit;
        }
    }

    /**
     * Penalidade de leitura da matriz, para escolher a melhor mascara.
     *
     * @param  array<int, array<int, bool>>  $matriz
     */
    private static function penalidade(array $matriz, int $lado): int
    {
        $total = 0;

        // regra 1: sequencias de 5 ou mais modulos iguais, em linha e coluna
        foreach ([true, false] as $porLinha) {
            for ($a = 0; $a < $lado; $a++) {
                $sequencia = 1;

                for ($b = 1; $b < $lado; $b++) {
                    $atual = $porLinha ? $matriz[$a][$b] : $matriz[$b][$a];
                    $anterior = $porLinha ? $matriz[$a][$b - 1] : $matriz[$b - 1][$a];

                    if ($atual === $anterior) {
                        $sequencia++;

                        continue;
                    }

                    if ($sequencia >= 5) {
                        $total += 3 + $sequencia - 5;
                    }

                    $sequencia = 1;
                }

                if ($sequencia >= 5) {
                    $total += 3 + $sequencia - 5;
                }
            }
        }

        // regra 2: blocos 2x2 de uma cor so
        for ($y = 0; $y < $lado - 1; $y++) {
            for ($x = 0; $x < $lado - 1; $x++) {
                $cor = $matriz[$y][$x];

                if ($matriz[$y][$x + 1] === $cor && $matriz[$y + 1][$x] === $cor && $matriz[$y + 1][$x + 1] === $cor) {
                    $total += 3;
                }
            }
        }

        // regra 3: padrao 1:1:3:1:1 com 4 claros de um lado, que imita o localizador
        $padroes = ['10111010000', '00001011101'];

        foreach ([true, false] as $porLinha) {
            for ($a = 0; $a < $lado; $a++) {
                $linha = '';

                for ($b = 0; $b < $lado; $b++) {
                    $linha .= ($porLinha ? $matriz[$a][$b] : $matriz[$b][$a]) ? '1' : '0';
                }

                foreach ($padroes as $padrao) {
                    $total += 40 * substr_count($linha, $padrao);
                }
            }
        }

        // regra 4: desequilibrio entre claros e escuros
        $escuros = 0;

        foreach ($matriz as $linha) {
            $escuros += count(array_filter($linha));
        }

        $proporcao = $escuros * 100 / ($lado * $lado);
        $total += (int) (abs($proporcao - 50) / 5) * 10;

        return $total;
    }
}
