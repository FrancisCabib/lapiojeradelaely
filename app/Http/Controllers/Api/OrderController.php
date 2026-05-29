<?php

namespace App\Http\Controllers\Api;

use App\Actions\Orders\StoreOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreOrderRequest;
use App\Support\OrderSettings;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request, StoreOrderAction $action): JsonResponse
    {
        $order = $action->execute($request->validated());

        return response()->json([
            'order' => [
                'id' => $order->id,
                'reference_code' => $order->reference_code,
                'status' => $order->status->value,
                'status_label' => $order->statusLabel(),
                'payment_method' => $order->payment_method->value,
                'total_amount' => $order->total_amount,
                'deposit_amount' => $order->deposit_amount,
                'deposit_percent' => $order->deposit_percent,
                'formatted_total' => $order->formattedTotal(),
                'formatted_deposit' => $order->formattedDeposit(),
            ],
            'order_settings' => OrderSettings::toArray(),
        ], 201);
    }
}
