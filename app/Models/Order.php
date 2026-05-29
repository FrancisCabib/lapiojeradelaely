<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'reference_code',
        'customer_name',
        'order_summary',
        'scheduled_date',
        'scheduled_time',
        'payment_method',
        'total_amount',
        'deposit_amount',
        'deposit_percent',
        'status',
        'notes',
        'is_custom',
        'items',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'total_amount' => 'integer',
            'deposit_amount' => 'integer',
            'deposit_percent' => 'integer',
            'is_custom' => 'boolean',
            'items' => 'array',
            'confirmed_at' => 'datetime',
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
        ];
    }

    public static function generateReferenceCode(): string
    {
        do {
            $code = 'PED-'.now()->format('ymd').'-'.Str::upper(Str::random(4));
        } while (self::query()->where('reference_code', $code)->exists());

        return $code;
    }

    public function statusLabel(): string
    {
        return $this->status->label();
    }

    public function paymentMethodLabel(): string
    {
        return $this->payment_method->label();
    }

    public function formattedTotal(): ?string
    {
        return is_null($this->total_amount)
            ? null
            : '$'.number_format($this->total_amount, 0, ',', '.');
    }

    public function formattedDeposit(): ?string
    {
        return is_null($this->deposit_amount)
            ? null
            : '$'.number_format($this->deposit_amount, 0, ',', '.');
    }
}
