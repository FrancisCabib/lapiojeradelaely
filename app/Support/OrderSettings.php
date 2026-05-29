<?php

namespace App\Support;

class OrderSettings
{
    public static function depositPercent(): int
    {
        $percent = (int) config('business.reservation_deposit_percent', 50);

        return max(0, min(100, $percent));
    }

    public static function depositAmount(?int $totalAmount, ?string $paymentMethod = null): ?int
    {
        if ($totalAmount === null) {
            return null;
        }

        $percent = self::depositPercent();
        if ($percent <= 0) {
            return null;
        }

        return (int) round($totalAmount * $percent / 100);
    }

    public static function initialStatus(?string $paymentMethod = null): string
    {
        return 'pending_deposit';
    }

    /** @return array<string, mixed> */
    public static function toArray(): array
    {
        return [
            'deposit_percent' => self::depositPercent(),
            'whatsapp_phone' => config('business.whatsapp_order_phone'),
            'bank' => [
                'name' => config('business.bank_name'),
                'account_type' => config('business.bank_account_type'),
                'account_number' => config('business.bank_account_number'),
                'holder' => config('business.bank_account_holder'),
                'rut' => config('business.bank_account_rut'),
                'email' => config('business.bank_account_email'),
            ],
        ];
    }
}
