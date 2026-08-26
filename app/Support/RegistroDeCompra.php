<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Grava um JSON por passo da compra, na pasta do cliente.
 *
 * Sao tres passos, e cada um vira um arquivo separado -- nao um so no fim:
 *
 *   1-selecao.json      o que o cliente escolheu (pecas, variantes, subtotal)
 *   2-pagamento.json    a cobranca emitida (forma, codigo, frete, total, IP)
 *   3-conferencia.json  o que o banco respondeu sobre essa cobranca
 *
 * Ficam em storage/app/private/historico-clientes/{user_id}/pedidos/{order_id}/,
 * fora do alcance publico. A pasta e por cliente porque o destino desses dados
 * e o historico dele: montar o documento de historico vira ler a pasta inteira,
 * em ordem, sem tocar no banco.
 *
 * Nenhum metodo daqui pode derrubar uma compra. Registro e efeito colateral: se
 * o disco estiver cheio, sem permissao ou somente-leitura, o pedido ja foi pago
 * e tem de seguir. Por isso todo IO esta dentro de try/catch e o erro so vai
 * para o log -- foi exatamente esse caso (storage/app/private com dono root,
 * inacessivel ao www-data) que fazia "Confirmar pedido" devolver 500 depois de
 * ja ter criado o pedido, baixado o estoque e esvaziado o carrinho.
 */
class RegistroDeCompra
{
    private const DISCO = 'local';

    public const PASSO_SELECAO = '1-selecao';

    public const PASSO_PAGAMENTO = '2-pagamento';

    public const PASSO_CONFERENCIA = '3-conferencia';

    /**
     * Passo 1: as pecas escolhidas, congeladas no momento da compra.
     *
     * Grava preco e variante de cada item porque o produto muda de preco e o
     * estoque muda de variante -- reler pelo product_id daqui a um ano nao
     * devolveria o que o cliente comprou.
     */
    public static function registrarSelecao(Order $order): ?string
    {
        $order->loadMissing('items.product.loja', 'user');

        $itens = $order->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'nome' => $item->product?->nome,
            'categoria' => $item->product?->categoria,
            'loja' => $item->product?->loja?->nomeExibicao(),
            'tamanho' => $item->tamanho,
            'cor' => $item->cor,
            'quantidade' => $item->quantidade,
            'preco_unitario' => (float) $item->preco_unitario,
            'subtotal' => round((float) $item->preco_unitario * $item->quantidade, 2),
        ])->all();

        return self::gravar($order, self::PASSO_SELECAO, [
            'itens' => $itens,
            'quantidade_total' => $order->items->sum('quantidade'),
            'subtotal' => (float) $order->total,
        ]);
    }

    /**
     * Passo 2: a cobranca emitida, com a composicao do valor e o frete.
     *
     * @param  array<string, mixed>  $cobranca  resposta do gateway
     */
    public static function registrarPagamento(Order $order, array $cobranca): ?string
    {
        return self::gravar($order, self::PASSO_PAGAMENTO, [
            'forma_pagamento' => $order->forma_pagamento,
            'status_pagamento' => $order->status_pagamento,
            'codigo_pagamento' => $order->codigo_pagamento,
            'valores' => [
                'subtotal' => (float) $order->total,
                'frete' => (float) ($order->valor_frete ?? 0),
                'total' => round((float) $order->total + (float) ($order->valor_frete ?? 0), 2),
            ],
            'entrega' => [
                'tipo' => $order->tipo_entrega,
                'endereco' => $order->endereco_entrega,
                'distancia_km' => $order->distancia_km === null ? null : (float) $order->distancia_km,
            ],
            'origem_da_compra' => [
                'ip' => $order->ip_compra,
                'localizacao' => $order->localizacao,
            ],
            'gateway' => $cobranca,
        ]);
    }

    /**
     * Passo 3: o que o banco respondeu sobre a cobranca do passo 2.
     *
     * @param  array<string, mixed>  $conferencia  resposta do banco
     */
    public static function registrarConferencia(Order $order, array $conferencia): ?string
    {
        return self::gravar($order, self::PASSO_CONFERENCIA, [
            'codigo_pagamento' => $order->codigo_pagamento,
            'verificado_banco' => (bool) $order->verificado_banco,
            'status_pagamento' => $order->status_pagamento,
            'banco' => $conferencia,
        ]);
    }

    /** Pasta do pedido dentro do historico do cliente. */
    public static function pasta(Order $order): string
    {
        return 'historico-clientes/'.$order->user_id.'/pedidos/'.$order->id;
    }

    /**
     * Os JSONs ja gravados de um pedido, do passo 1 ao 3.
     *
     * E por aqui que o documento de historico do cliente vai ser montado depois
     * -- ler a pasta em ordem, sem depender de nenhuma consulta.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function passosRegistrados(Order $order): array
    {
        $passos = [];

        foreach ([self::PASSO_SELECAO, self::PASSO_PAGAMENTO, self::PASSO_CONFERENCIA] as $passo) {
            $caminho = self::pasta($order).'/'.$passo.'.json';

            try {
                if (! Storage::disk(self::DISCO)->exists($caminho)) {
                    continue;
                }

                $passos[$passo] = json_decode(Storage::disk(self::DISCO)->get($caminho), true);
            } catch (Throwable $e) {
                Log::warning('registro-compra.leitura_falhou', [
                    'order_id' => $order->id,
                    'passo' => $passo,
                    'erro' => $e->getMessage(),
                ]);
            }
        }

        return $passos;
    }

    /**
     * Escreve o JSON do passo e devolve o caminho relativo ao disco 'local'.
     *
     * Devolve null quando a gravacao falha -- quem chama segue em frente.
     *
     * @param  array<string, mixed>  $dados
     */
    private static function gravar(Order $order, string $passo, array $dados): ?string
    {
        $caminho = self::pasta($order).'/'.$passo.'.json';

        // cabecalho igual nos tres arquivos: cada JSON precisa se explicar
        // sozinho quando for lido fora do sistema, sem os outros dois por perto
        $conteudo = [
            'passo' => $passo,
            'registrado_em' => now()->toIso8601String(),
            'pedido' => [
                'id' => $order->id,
                'status' => $order->status,
                'criado_em' => $order->created_at?->toIso8601String(),
            ],
            'cliente' => [
                'id' => $order->user_id,
                'nome' => $order->user?->name,
                'email' => $order->user?->email,
            ],
        ] + $dados;

        try {
            $json = json_encode($conteudo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            // o disco 'local' esta com throw=false, mas o Flysystem ainda
            // levanta excecao quando nem consegue abrir a raiz do disco
            if (Storage::disk(self::DISCO)->put($caminho, $json) === false) {
                throw new \RuntimeException('Storage::put devolveu false.');
            }

            return $caminho;
        } catch (Throwable $e) {
            Log::warning('registro-compra.gravacao_falhou', [
                'order_id' => $order->id,
                'passo' => $passo,
                'caminho' => $caminho,
                'erro' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
