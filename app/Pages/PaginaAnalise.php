<?php

namespace App\Pages;

use App\Interfaces\Analise;
use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;

class PaginaAnalise implements Analise
{
    public function receberDoc(?int $lojaId = null): Collection
    {
        return Order::with('user', 'items.product')
            ->where('status_pagamento', 'aguardando_analise')
            ->when($lojaId, fn ($query) => $query->whereHas(
                'items.product', fn ($q) => $q->where('loja_id', $lojaId)
            ))
            ->latest()
            ->get();
    }

    public function aceitar(Order $order, int $analistaId): Order
    {
        $order->update([
            'status_pagamento' => 'aprovado',
            'analisado_por' => $analistaId,
            'analisado_em' => now(),
        ]);

        return $order;
    }

    public function recusar(Order $order, int $analistaId, ?string $motivo = null): Order
    {
        $order->update([
            'status_pagamento' => 'recusado',
            'status' => 'cancelado',
            'analisado_por' => $analistaId,
            'analisado_em' => now(),
        ]);

        return $order;
    }

    public function escolherEntrega(Order $order, bool $entregaPropria, ?int $transportadoraId = null, ?int $motoristaId = null): Order
    {
        $order->update([
            'entrega_propria' => $entregaPropria,
            'transportadora_id' => $entregaPropria ? null : $transportadoraId,
            'motorista_id' => $motoristaId,
        ]);

        return $order;
    }

    public function separar(Order $order): Order
    {
        $order->update(['status_separacao' => 'separado']);

        return $order;
    }

    public function enviar(Order $order): Order
    {
        $order->update(['status_separacao' => 'enviado']);

        return $order;
    }

    /**
     * Ultima etapa do pedido: chegou ao cliente.
     *
     * Nao mexe em status nem em status_pagamento -- 'concluido' ali significa
     * que a compra fechou, nao que a peca foi entregue. Sao trilhas separadas.
     */
    public function entregar(Order $order): Order
    {
        $order->update(['status_separacao' => 'entregue']);

        return $order;
    }

    public function embalagem(Order $order): Order
    {
        $order->update(['status_separacao' => 'embalado']);

        return $order;
    }

    public function fragil(Order $order, bool $ehFragil = true): Order
    {
        $order->update(['fragil' => $ehFragil]);

        return $order;
    }

    public function dimensao(Order $order, string $dimensoes): Order
    {
        $order->update(['dimensoes' => $dimensoes]);

        return $order;
    }
}
