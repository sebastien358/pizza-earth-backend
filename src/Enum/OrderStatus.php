<?php

namespace App\Enum;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case VALIDATED = 'validated';
    case CANCELLED = 'cancelled';
}
