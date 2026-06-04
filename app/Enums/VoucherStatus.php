<?php

namespace App\Enums;

enum VoucherStatus: string
{
    case Active = 'active';
    case Redeemed = 'redeemed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
}
