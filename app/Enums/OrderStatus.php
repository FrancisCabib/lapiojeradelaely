<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingDeposit = 'pending_deposit';
    case Confirmed = 'confirmed';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::PendingDeposit => 'Pendiente de adelanto',
            self::Confirmed => 'Confirmado',
            self::Delivered => 'Entregado',
            self::Cancelled => 'Cancelado',
            self::NoShow => 'No llegó',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
