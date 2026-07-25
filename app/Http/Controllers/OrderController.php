<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $pedidos = Order::with('items.product')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return view('orders.index', [
            'pedidosRealizados' => $pedidos->where('status', 'concluido')->values(),
            'pedidosCancelados' => $pedidos->where('status', 'cancelado')->values(),
        ]);
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        if ($order->status === 'concluido') {
            $order->update(['status' => 'cancelado']);
        }

        return back()->with('status', 'Pedido #'.$order->id.' cancelado.');
    }

    public function rate(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        abort_unless($order->status === 'concluido', 422);

        $data = $request->validate([
            'avaliacao' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $order->update(['avaliacao' => $data['avaliacao']]);

        return back()->with('status', 'Obrigado por avaliar o pedido #'.$order->id.'!');
    }
}
