<?php

namespace App\Support;

use App\Classes\Loja;
use App\Models\OrderItem;
use App\Models\Saque;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Carteira da loja: quanto ela ja pode sacar e quanto ainda esta a caminho.
 *
 * Nao existe coluna de saldo. Tudo e derivado dos pedidos e dos saques, porque
 * um numero guardado em `lojas` divergiria das vendas na primeira falha de
 * escrita e ninguem saberia qual dos dois esta certo. Reconstruir a conta
 * custa duas consultas e nunca mente.
 *
 * A regra de liberacao:
 *
 *   A RECEBER   pedido pago (aprovado ou em analise) que ainda nao chegou ao
 *               cliente. E dinheiro do lojista, mas preso ate a entrega --
 *               antes disso a compra ainda pode ser cancelada ou devolvida.
 *   DISPONIVEL  pedido entregue (status_separacao = 'entregue'), menos o que
 *               ja foi sacado.
 *
 * Os dois valores sao LIQUIDOS: a comissao da plataforma ja saiu. O bruto e a
 * comissao aparecem separados em resumo() para a loja conferir a conta.
 */
class CarteiraDaLoja
{
    /**
     * Comissao da plataforma, em porcentagem.
     *
     * App\Classes\Plano preve comissao por plano, mas a tabela `planos` nao
     * existe e nenhuma loja tem plano_id -- entao a taxa e uma so por
     * enquanto. Quando os planos entrarem, esta constante vira a taxa padrao.
     */
    public const COMISSAO_PADRAO = 10.0;

    /** Situacoes de pagamento em que o dinheiro conta como do lojista. */
    private const PAGAMENTOS_VALIDOS = ['aprovado', 'aguardando_analise'];

    /**
     * Resumo da carteira, com bruto e comissao a vista.
     *
     * @return array{disponivel: float, a_receber: float, sacado: float, bruto_total: float, comissao: float, taxa: float}
     */
    public static function resumo(Loja $loja): array
    {
        $entregue = self::brutoPorEntrega($loja->id, true);
        $emTransito = self::brutoPorEntrega($loja->id, false);

        $sacado = (float) Saque::where('loja_id', $loja->id)
            ->where('status', '!=', 'cancelado')
            ->sum('valor');

        $liquidoEntregue = self::liquido($entregue);

        return [
            // saque nao pode deixar o saldo negativo nem que a conta mude depois
            'disponivel' => round(max(0, $liquidoEntregue - $sacado), 2),
            'a_receber' => self::liquido($emTransito),
            'sacado' => round($sacado, 2),
            'bruto_total' => round($entregue + $emTransito, 2),
            'comissao' => round(($entregue + $emTransito) * (self::COMISSAO_PADRAO / 100), 2),
            'taxa' => self::COMISSAO_PADRAO,
        ];
    }

    /**
     * Registra um saque, descontando do disponivel.
     *
     * @throws ValidationException quando o valor nao cabe no saldo
     */
    public static function sacar(Loja $loja, float $valor, ?string $destino = null): Saque
    {
        $disponivel = self::resumo($loja)['disponivel'];

        if ($valor <= 0) {
            throw ValidationException::withMessages([
                'valor' => 'Informe um valor maior que zero.',
            ]);
        }

        if ($valor > $disponivel) {
            throw ValidationException::withMessages([
                'valor' => 'Saldo disponível insuficiente. Você pode sacar até R$ '.number_format($disponivel, 2, ',', '.').'.',
            ]);
        }

        return Saque::create([
            'loja_id' => $loja->id,
            'valor' => round($valor, 2),
            'status' => 'solicitado',
            // sem integracao bancaria, o destino e o que a loja cadastrou no
            // proprio perfil; vira dado do gateway quando houver repasse real
            'destino' => $destino ?: trim($loja->banco.' · '.$loja->conta, ' ·') ?: null,
        ]);
    }

    /** Os ultimos saques da loja, para o extrato da tela. */
    public static function extrato(Loja $loja, int $limite = 10)
    {
        return Saque::where('loja_id', $loja->id)->latest()->take($limite)->get();
    }

    /**
     * Faturamento bruto da loja, separado entre o que ja foi entregue e o que
     * ainda esta em transito.
     *
     * So o valor das pecas entra: o frete e da entrega, nao da loja.
     */
    private static function brutoPorEntrega(int $lojaId, bool $entregue): float
    {
        return (float) OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('products.loja_id', $lojaId)
            ->where('orders.status', '!=', 'cancelado')
            ->whereIn('orders.status_pagamento', self::PAGAMENTOS_VALIDOS)
            ->when(
                $entregue,
                fn ($q) => $q->where('orders.status_separacao', 'entregue'),
                fn ($q) => $q->where(fn ($sub) => $sub->whereNull('orders.status_separacao')
                    ->orWhere('orders.status_separacao', '!=', 'entregue')),
            )
            ->sum(DB::raw('order_items.quantidade * order_items.preco_unitario'));
    }

    /** Desconta a comissao da plataforma. */
    private static function liquido(float $bruto): float
    {
        return round($bruto * (1 - self::COMISSAO_PADRAO / 100), 2);
    }
}
