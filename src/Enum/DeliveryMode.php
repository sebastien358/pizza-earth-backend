<?php

namespace App\Enum;

enum DeliveryMode: string
{
    case DELIVERY = 'delivery'; // Livraison
    case PICKUP = 'pickup';     // Retrait
}


