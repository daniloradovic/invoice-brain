<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Paid => 'Paid',
            self::Overdue => 'Overdue',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft => 'gray',
            self::Sent => 'blue',
            self::Paid => 'green',
            self::Overdue => 'red',
            self::Cancelled => 'gray',
        };
    }

    public static function paidStatuses(): array
    {
        return [self::Paid];
    }

    public static function openStatuses(): array
    {
        return [self::Sent, self::Overdue];
    }
}
