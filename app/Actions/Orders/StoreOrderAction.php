<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Support\OrderSettings;
use Illuminate\Support\Carbon;

class StoreOrderAction
{
    /** @param  array<string, mixed>  $data */
    public function execute(array $data): Order
    {
        $paymentMethod = PaymentMethod::from($data['payment_method']);
        $totalAmount = isset($data['total_amount']) ? (int) $data['total_amount'] : null;
        $depositPercent = OrderSettings::depositPercent();
        $depositAmount = OrderSettings::depositAmount($totalAmount);
        $initialStatus = OrderStatus::PendingDeposit;

        return Order::query()->create([
            'reference_code' => Order::generateReferenceCode(),
            'customer_name' => $data['customer_name'],
            'order_summary' => $data['order_summary'],
            'scheduled_date' => Carbon::parse($data['scheduled_date'])->toDateString(),
            'scheduled_time' => $data['scheduled_time'],
            'payment_method' => $paymentMethod,
            'total_amount' => $totalAmount,
            'deposit_amount' => $depositAmount,
            'deposit_percent' => $depositPercent,
            'status' => $initialStatus,
            'notes' => $data['notes'] ?? null,
            'is_custom' => (bool) ($data['is_custom'] ?? false),
            'items' => $data['items'] ?? null,
            'confirmed_at' => null,
        ]);
    }
}
