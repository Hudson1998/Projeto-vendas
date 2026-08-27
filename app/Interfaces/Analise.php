<?php

namespace App\Interfaces;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;

interface Analise
{
    public function receberDoc(?int $lojaId = null): Collection;

    public function aceitar(Order $order, int $analistaId): Order;

    public function recusar(Order $order, int $analistaId, ?string $motivo = null): Order;

    public function escolherEntrega(Order $order, bool $entregaPropria, ?int $transportadoraId = null, ?int $motoristaId = null): Order;

    public function separar(Order $order): Order;

    public function enviar(Order $order): Order;

    public function entregar(Order $order): Order;

    public function embalagem(Order $order): Order;

    public function fragil(Order $order, bool $ehFragil = true): Order;

    public function dimensao(Order $order, string $dimensoes): Order;
}
