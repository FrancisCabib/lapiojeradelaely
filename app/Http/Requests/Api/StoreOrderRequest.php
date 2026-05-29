<?php

namespace App\Http\Requests\Api;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:120'],
            'order_summary' => ['required', 'string', 'max:4000'],
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'scheduled_time' => ['required', 'date_format:H:i'],
            'payment_method' => ['required', Rule::in(PaymentMethod::values())],
            'total_amount' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_custom' => ['sometimes', 'boolean'],
            'items' => ['nullable', 'array'],
            'items.*.title' => ['required_with:items', 'string', 'max:255'],
            'items.*.price' => ['nullable', 'integer', 'min:0'],
            'items.*.meal' => ['nullable', 'string', 'max:120'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'customer_name.required' => 'Indica tu nombre.',
            'scheduled_date.after_or_equal' => 'La fecha debe ser hoy o posterior.',
            'payment_method.in' => 'Forma de pago no válida.',
        ];
    }
}
