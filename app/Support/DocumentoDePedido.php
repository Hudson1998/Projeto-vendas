<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Documentos que a loja guarda de cada pedido, um por etapa do fluxo.
 *
 * Sao tres momentos em que alguem assume responsabilidade pela mercadoria, e
 * cada um deixa um arquivo em
 * storage/app/private/documentos-lojas/{loja_id}/pedidos/{order_id}/:
 *
 *   1-aceite.json      a loja aceitou o pedido e vai separar
 *   2-transporte.json  a mercadoria saiu da loja para quem transporta
 *   3-entrega.json     chegou as maos do cliente
 *
 * Sao JSON e nao texto porque o destino deles e a tela do lojista, que precisa
 * ler os campos de volta -- e porque a mesma pasta vira anexo de disputa se
 * cliente e loja discordarem de quem estava com a peca.
 *
 * Como no registro da compra, gravar aqui nunca pode derrubar a acao: quando o
 * documento falha, o pedido ja mudou de etapa. Falha vira aviso no log.
 */
class DocumentoDePedido
{
    private const DISCO = 'local';

    public const ACEITE = '1-aceite';

    public const TRANSPORTE = '2-transporte';

    public const ENTREGA = '3-entrega';

    /** @var array<string, string> rotulo de cada etapa, para a tela */
    public const ROTULOS = [
        self::ACEITE => 'Pedido aceito pela loja',
        self::TRANSPORTE => 'Entregue ao transporte',
        self::ENTREGA => 'Entregue ao cliente',
    ];

    /** A loja aceitou o pedido e ele entra na separacao. */
    public static function registrarAceite(Order $order, int $lojaId): ?string
    {
        return self::gravar($order, $lojaId, self::ACEITE, [
            'aceito_em' => now()->toIso8601String(),
            'prazo_separacao' => 'até 1 dia útil',
        ]);
    }

    /** A mercadoria saiu da loja: quem leva assume a partir daqui. */
    public static function registrarTransporte(Order $order, int $lojaId): ?string
    {
        $order->loadMissing('transportadora', 'motorista');

        return self::gravar($order, $lojaId, self::TRANSPORTE, [
            'despachado_em' => now()->toIso8601String(),
            'responsavel' => $order->entrega_propria
                ? 'Entrega própria da loja'
                : ($order->transportadora?->nome ?? 'Transportadora não informada'),
            'motorista' => $order->motorista?->nome,
            'volume' => [
                'fragil' => (bool) $order->fragil,
                'dimensoes' => $order->dimensoes,
            ],
        ]);
    }

    /** A peca chegou ao cliente: fim da responsabilidade da loja. */
    public static function registrarEntrega(Order $order, int $lojaId): ?string
    {
        return self::gravar($order, $lojaId, self::ENTREGA, [
            'entregue_em' => now()->toIso8601String(),
            'endereco' => $order->endereco_entrega,
            'recebedor' => $order->user?->name,
        ]);
    }

    /** Pasta dos documentos de um pedido, dentro da loja. */
    public static function pasta(Order $order, int $lojaId): string
    {
        return 'documentos-lojas/'.$lojaId.'/pedidos/'.$order->id;
    }

    /**
     * Os documentos ja emitidos de um pedido, na ordem das etapas.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function doPedido(Order $order, int $lojaId): array
    {
        $documentos = [];

        foreach (array_keys(self::ROTULOS) as $etapa) {
            $caminho = self::pasta($order, $lojaId).'/'.$etapa.'.json';

            try {
                if (Storage::disk(self::DISCO)->exists($caminho)) {
                    $documentos[$etapa] = json_decode(Storage::disk(self::DISCO)->get($caminho), true);
                }
            } catch (Throwable $e) {
                Log::warning('documento-pedido.leitura_falhou', [
                    'order_id' => $order->id,
                    'etapa' => $etapa,
                    'erro' => $e->getMessage(),
                ]);
            }
        }

        return $documentos;
    }

    /**
     * Todos os documentos da loja, do mais recente para o mais antigo.
     *
     * Le a pasta em vez do banco: os documentos sao a fonte, e assim a tela
     * mostra exatamente o que existe em disco -- inclusive de pedido que por
     * algum motivo nao esteja mais na consulta.
     *
     * @return array<int, array{order_id: int, etapa: string, rotulo: string, dados: array<string, mixed>}>
     */
    public static function daLoja(int $lojaId, int $limite = 60): array
    {
        $raiz = 'documentos-lojas/'.$lojaId.'/pedidos';
        $documentos = [];

        try {
            foreach (Storage::disk(self::DISCO)->directories($raiz) as $pastaPedido) {
                $orderId = (int) basename($pastaPedido);

                foreach (Storage::disk(self::DISCO)->files($pastaPedido) as $arquivo) {
                    $etapa = pathinfo($arquivo, PATHINFO_FILENAME);

                    if (! isset(self::ROTULOS[$etapa])) {
                        continue;
                    }

                    $documentos[] = [
                        'order_id' => $orderId,
                        'etapa' => $etapa,
                        'rotulo' => self::ROTULOS[$etapa],
                        'dados' => json_decode(Storage::disk(self::DISCO)->get($arquivo), true) ?: [],
                    ];
                }
            }
        } catch (Throwable $e) {
            Log::warning('documento-pedido.listagem_falhou', [
                'loja_id' => $lojaId,
                'erro' => $e->getMessage(),
            ]);

            return [];
        }

        // mais recentes primeiro: pedido maior e etapa mais adiantada
        usort($documentos, fn ($a, $b) => [$b['order_id'], $b['etapa']] <=> [$a['order_id'], $a['etapa']]);

        return array_slice($documentos, 0, $limite);
    }

    /**
     * Escreve o documento e devolve o caminho, ou null se a gravacao falhar.
     *
     * @param  array<string, mixed>  $dados
     */
    private static function gravar(Order $order, int $lojaId, string $etapa, array $dados): ?string
    {
        $order->loadMissing('user', 'items.product');

        $caminho = self::pasta($order, $lojaId).'/'.$etapa.'.json';

        $conteudo = [
            'etapa' => $etapa,
            'titulo' => self::ROTULOS[$etapa],
            'emitido_em' => now()->toIso8601String(),
            'loja_id' => $lojaId,
            'pedido' => [
                'id' => $order->id,
                'realizado_em' => $order->created_at?->toIso8601String(),
                'forma_pagamento' => $order->forma_pagamento,
                'codigo_pagamento' => $order->codigo_pagamento,
                'tipo_entrega' => $order->tipo_entrega,
                'total_pecas' => round((float) $order->total, 2),
                'frete' => round((float) ($order->valor_frete ?? 0), 2),
            ],
            'cliente' => [
                'id' => $order->user_id,
                'nome' => $order->user?->name,
                'email' => $order->user?->email,
            ],
            'itens' => $order->items->map(fn ($item) => [
                'produto' => $item->product?->nome,
                'tamanho' => $item->tamanho,
                'cor' => $item->cor,
                'quantidade' => $item->quantidade,
                'preco_unitario' => (float) $item->preco_unitario,
            ])->all(),
        ] + $dados;

        try {
            $json = json_encode($conteudo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if (Storage::disk(self::DISCO)->put($caminho, $json) === false) {
                throw new \RuntimeException('Storage::put devolveu false.');
            }

            return $caminho;
        } catch (Throwable $e) {
            Log::warning('documento-pedido.gravacao_falhou', [
                'order_id' => $order->id,
                'etapa' => $etapa,
                'erro' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
