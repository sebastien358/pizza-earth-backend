<?php

namespace App\Services;

use App\Repository\OrderRepository;

class OrderService
{
    public function ordersDisplay($serializer, $orders)
    {
        if (is_array($orders)) {
            $dataOrders = $serializer->normalize($orders, 'json', ['groups' => ['orders', 'items', 'user'],
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);
            return $dataOrders;
        }
        return [];
    }
}
