<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(): Response
    {
        $statusFilter = request()->string('status')->toString();

        $orders = Order::query()
            ->when(
                $statusFilter !== '' && in_array($statusFilter, OrderStatus::values(), true),
                fn ($query) => $query->where('status', $statusFilter),
            )
            ->orderByDesc('scheduled_date')
            ->orderByDesc('scheduled_time')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Order $order) => [
                'id' => $order->id,
                'reference_code' => $order->reference_code,
                'customer_name' => $order->customer_name,
                'order_summary' => $order->order_summary,
                'scheduled_date' => $order->scheduled_date->format('Y-m-d'),
                'scheduled_date_label' => $order->scheduled_date->format('d/m/Y'),
                'scheduled_time' => $order->scheduled_time,
                'payment_method' => $order->payment_method->value,
                'payment_method_label' => $order->paymentMethodLabel(),
                'total_amount' => $order->total_amount,
                'deposit_amount' => $order->deposit_amount,
                'formatted_total' => $order->formattedTotal(),
                'formatted_deposit' => $order->formattedDeposit(),
                'status' => $order->status->value,
                'status_label' => $order->statusLabel(),
                'notes' => $order->notes,
                'is_custom' => $order->is_custom,
                'created_at' => $order->created_at?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('admin/Orders/Index', [
            'orders' => $orders,
            'statusFilter' => $statusFilter,
            'statusOptions' => collect(OrderStatus::cases())->map(fn (OrderStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values(),
        ]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $status = OrderStatus::from($request->validated('status'));

        $order->status = $status;

        if ($status === OrderStatus::Confirmed && $order->confirmed_at === null) {
            $order->confirmed_at = now();
        }

        if (in_array($status, [OrderStatus::Cancelled, OrderStatus::NoShow], true)) {
            $order->confirmed_at = null;
        }

        $order->save();

        return back()->with('success', "Pedido {$order->reference_code} actualizado a «{$status->label()}».");
    }
}
