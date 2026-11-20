<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case PaymentFailed = 'payment_failed';

    public function isPaid(): bool
    {
        return in_array($this, [self::Paid, self::Processing, self::Shipped, self::Delivered], true);
    }

    /**
     * @return list<self>
     */
    public function adminTransitions(): array
    {
        return match ($this) {
            self::Paid => [self::Processing, self::Cancelled],
            self::Processing => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered],
            self::PendingPayment => [self::Cancelled],
            default => [],
        };
    }
}
