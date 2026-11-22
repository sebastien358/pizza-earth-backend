<?php

namespace App\Services;

use Symfony\Component\Serializer\SerializerInterface;

class OrderService
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function ordersDisplay($orders)
    {
        if (is_array($orders)) {
            $dataOrders = $this->serializer->normalize($orders, 'json', ['groups' => ['orders', 'items', 'products', 'user'],
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);
            return $dataOrders;
        }
        return [];
    }
}
