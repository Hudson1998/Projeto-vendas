<?php

namespace App\Support;

use App\Classes\Loja;
use App\Models\User;

/**
 * Distancia, em km, entre o ponto de despacho da loja e o endereco do cliente.
 *
 * Nao existe geocodificador no projeto -- nem chave de API, nem colunas de
 * latitude/longitude no banco. O que existe e o municipio: a loja guarda
 * envio_cidade/envio_estado no cadastro e o endereco do cliente termina em
 * "Cidade - UF". Entao a rota sai de uma tabela local de coordenadas de
 * municipios, por haversine, sem nenhuma chamada externa -- e o checkout nunca
 * depende de rede para calcular frete.
 *
 * Quando houver geocodificacao de verdade (CEP -> lat/lng), so este arquivo
 * muda: quem chama pede km e continua pedindo km.
 */
class RotaDeEntrega
{
    /**
     * Quanto a estrada e mais longa que a linha reta.
     *
     * Haversine mede o voo do passaro; a entrega anda por rodovia. 1,3 e a
     * folga usual entre distancia geodesica e rodoviaria no Brasil.
     */
    private const FATOR_RODOVIARIO = 1.3;

    /**
     * Rota assumida quando loja e cliente estao na mesma cidade.
     *
     * Dentro do municipio a coordenada do centro nao diz nada -- os dois pontos
     * cairiam sobre si mesmos e a distancia daria zero. 6 km e o teto da faixa
     * base do frete, entao entrega na mesma cidade custa o minimo de R$ 12,00.
     */
    private const KM_MESMA_CIDADE = 6.0;

    /**
     * Rota assumida quando nem a cidade nem a UF foram reconhecidas.
     *
     * Cobrar por um palpite alto seria pior do que cobrar o minimo: o cliente
     * nao tem como contestar uma distancia que o sistema inventou.
     */
    private const KM_DESCONHECIDO = self::KM_MESMA_CIDADE;

    /**
     * Coordenadas (lat, lng) do centro dos municipios que aparecem no cadastro.
     *
     * Cobre as 27 capitais e as maiores cidades nao-capitais. Cidade fora da
     * lista cai na capital da propria UF -- ver capitalDe().
     *
     * @var array<string, array{float, float}>
     */
    private const MUNICIPIOS = [
        // capitais
        'rio branco/ac' => [-9.9747, -67.8100],
        'maceio/al' => [-9.6658, -35.7353],
        'macapa/ap' => [0.0349, -51.0694],
        'manaus/am' => [-3.1190, -60.0217],
        'salvador/ba' => [-12.9777, -38.5016],
        'fortaleza/ce' => [-3.7319, -38.5267],
        'brasilia/df' => [-15.7939, -47.8828],
        'vitoria/es' => [-20.3155, -40.3128],
        'goiania/go' => [-16.6869, -49.2648],
        'sao luis/ma' => [-2.5387, -44.2825],
        'cuiaba/mt' => [-15.6014, -56.0979],
        'campo grande/ms' => [-20.4697, -54.6201],
        'belo horizonte/mg' => [-19.9167, -43.9345],
        'belem/pa' => [-1.4558, -48.5044],
        'joao pessoa/pb' => [-7.1195, -34.8450],
        'curitiba/pr' => [-25.4284, -49.2733],
        'recife/pe' => [-8.0476, -34.8770],
        'teresina/pi' => [-5.0892, -42.8019],
        'rio de janeiro/rj' => [-22.9068, -43.1729],
        'natal/rn' => [-5.7945, -35.2110],
        'porto alegre/rs' => [-30.0346, -51.2177],
        'porto velho/ro' => [-8.7612, -63.9004],
        'boa vista/rr' => [2.8235, -60.6758],
        'florianopolis/sc' => [-27.5954, -48.5480],
        'sao paulo/sp' => [-23.5505, -46.6333],
        'aracaju/se' => [-10.9472, -37.0731],
        'palmas/to' => [-10.1849, -48.3336],

        // nao-capitais de porte relevante
        'campinas/sp' => [-22.9099, -47.0626],
        'guarulhos/sp' => [-23.4538, -46.5333],
        'sao bernardo do campo/sp' => [-23.6939, -46.5650],
        'santo andre/sp' => [-23.6639, -46.5383],
        'osasco/sp' => [-23.5329, -46.7918],
        'sorocaba/sp' => [-23.5015, -47.4526],
        'ribeirao preto/sp' => [-21.1775, -47.8103],
        'santos/sp' => [-23.9608, -46.3336],
        'sao jose dos campos/sp' => [-23.1791, -45.8872],
        'niteroi/rj' => [-22.8832, -43.1034],
        'duque de caxias/rj' => [-22.7856, -43.3117],
        'sao goncalo/rj' => [-22.8268, -43.0634],
        'nova iguacu/rj' => [-22.7592, -43.4511],
        'uberlandia/mg' => [-18.9186, -48.2772],
        'contagem/mg' => [-19.9321, -44.0539],
        'juiz de fora/mg' => [-21.7642, -43.3503],
        'londrina/pr' => [-23.3045, -51.1696],
        'maringa/pr' => [-23.4253, -51.9386],
        'joinville/sc' => [-26.3044, -48.8456],
        'blumenau/sc' => [-26.9194, -49.0661],
        'caxias do sul/rs' => [-29.1685, -51.1794],
        'canoas/rs' => [-29.9177, -51.1839],
        'pelotas/rs' => [-31.7654, -52.3376],
        'feira de santana/ba' => [-12.2664, -38.9663],
        'jaboatao dos guararapes/pe' => [-8.1128, -35.0147],
        'olinda/pe' => [-8.0089, -34.8553],
        'caucaia/ce' => [-3.7361, -38.6531],
        'aparecida de goiania/go' => [-16.8198, -49.2469],
        'ananindeua/pa' => [-1.3656, -48.3722],
        'serra/es' => [-20.1288, -40.3078],
        'vila velha/es' => [-20.3297, -40.2925],
    ];

    /** UF -> capital, para cidade que nao esta na tabela acima. */
    private const CAPITAIS = [
        'ac' => 'rio branco', 'al' => 'maceio', 'ap' => 'macapa', 'am' => 'manaus',
        'ba' => 'salvador', 'ce' => 'fortaleza', 'df' => 'brasilia', 'es' => 'vitoria',
        'go' => 'goiania', 'ma' => 'sao luis', 'mt' => 'cuiaba', 'ms' => 'campo grande',
        'mg' => 'belo horizonte', 'pa' => 'belem', 'pb' => 'joao pessoa', 'pr' => 'curitiba',
        'pe' => 'recife', 'pi' => 'teresina', 'rj' => 'rio de janeiro', 'rn' => 'natal',
        'rs' => 'porto alegre', 'ro' => 'porto velho', 'rr' => 'boa vista', 'sc' => 'florianopolis',
        'sp' => 'sao paulo', 'se' => 'aracaju', 'to' => 'palmas',
    ];

    /**
     * Rota da loja ate o cliente, em km, ja com o fator rodoviario aplicado.
     *
     * Os dois pontos sao opcionais de proposito: loja sem endereco de envio e
     * cliente sem cidade reconhecivel caem no minimo em vez de quebrar a compra.
     */
    public static function km(?string $origem, ?string $destino): float
    {
        $pontoOrigem = self::localizar($origem);
        $pontoDestino = self::localizar($destino);

        if ($pontoOrigem === null || $pontoDestino === null) {
            return self::KM_DESCONHECIDO;
        }

        if ($pontoOrigem['chave'] === $pontoDestino['chave']) {
            return self::KM_MESMA_CIDADE;
        }

        $distancia = self::haversine(
            $pontoOrigem['lat'], $pontoOrigem['lng'],
            $pontoDestino['lat'], $pontoDestino['lng'],
        ) * self::FATOR_RODOVIARIO;

        return round($distancia, 2);
    }

    /**
     * Rota do pedido inteiro: o trecho mais longo entre as lojas envolvidas.
     *
     * O carrinho pode juntar pecas de lojas diferentes. Cobrar pela loja mais
     * proxima deixaria o trecho mais caro sem cobertura, entao vale a maior
     * distancia -- e uma unica entrega precisa alcancar todas elas.
     *
     * @param  iterable<int, Loja>  $lojas
     */
    public static function kmDoPedido(iterable $lojas, User $cliente): float
    {
        $maiorDistancia = null;

        foreach ($lojas as $loja) {
            $distancia = self::km($loja->enderecoDespacho(), $cliente->endereco);

            $maiorDistancia = $maiorDistancia === null ? $distancia : max($maiorDistancia, $distancia);
        }

        // pedido sem loja identificada (produto orfao) ainda precisa de um frete
        return $maiorDistancia ?? self::KM_DESCONHECIDO;
    }

    /**
     * Cidade/UF reconhecidas dentro de um endereco livre, com coordenadas.
     *
     * Aceita tanto o texto solto do cliente ("Rua X, 12 - Centro, Sao Paulo -
     * SP") quanto o "Cidade - UF" montado a partir do cadastro da loja.
     *
     * @return array{chave: string, cidade: string, uf: string, lat: float, lng: float}|null
     */
    public static function localizar(?string $endereco): ?array
    {
        if (blank($endereco)) {
            return null;
        }

        // a UF e sempre a ultima sigla de duas letras do endereco; o trecho
        // imediatamente antes dela, ate a virgula, e a cidade
        if (! preg_match('/([^,\-]+?)\s*[-\/]\s*([A-Za-z]{2})\s*\.?$/u', trim($endereco), $partes)) {
            return null;
        }

        $cidade = self::normalizar($partes[1]);
        $uf = mb_strtolower($partes[2]);

        if (! isset(self::CAPITAIS[$uf])) {
            return null;
        }

        $chave = $cidade.'/'.$uf;

        // cidade fora da tabela: usa a capital da UF, que e o melhor palpite
        // disponivel sem geocodificador
        if (! isset(self::MUNICIPIOS[$chave])) {
            $cidade = self::CAPITAIS[$uf];
            $chave = $cidade.'/'.$uf;
        }

        [$lat, $lng] = self::MUNICIPIOS[$chave];

        return ['chave' => $chave, 'cidade' => $cidade, 'uf' => $uf, 'lat' => $lat, 'lng' => $lng];
    }

    /** Distancia geodesica em km entre dois pares lat/lng. */
    private static function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $raioTerra = 6371.0;

        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($deltaLng / 2) ** 2;

        return $raioTerra * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Chave de comparacao de cidade: minuscula, sem acento e sem espaco duplo.
     *
     * O cadastro da loja grava "Sao Paulo" e o endereco do cliente traz
     * "São Paulo" -- sem normalizar, a mesma cidade viraria duas.
     */
    private static function normalizar(string $texto): string
    {
        $semAcento = strtr(mb_strtolower(trim($texto)), [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'ê' => 'e', 'è' => 'e', 'ë' => 'e',
            'í' => 'i', 'î' => 'i', 'ì' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ò' => 'o', 'ö' => 'o',
            'ú' => 'u', 'û' => 'u', 'ù' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
        ]);

        return preg_replace('/\s+/', ' ', $semAcento) ?? $semAcento;
    }
}
