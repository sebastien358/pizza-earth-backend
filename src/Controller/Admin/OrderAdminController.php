<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Services\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/admin')]
class OrderAdminController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private OrderService $orderService;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, OrderService $orderService, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->orderService = $orderService;
        $this->logger = $logger;
    }

    #[Route('/orders', methods: ['GET'])]
    public function index(SerializerInterface $serializer): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['erreur' => 'Utilisateur introuvable'], Response::HTTP_UNAUTHORIZED);
            }

            $orders = $this->entityManager->getRepository(Order::class)->findAllAdminOrders();
            if (!$orders) {
                return $this->json(['error' => 'Aucune commandes'], Response::HTTP_BAD_REQUEST);
            }

            $dataOrders = $this->orderService->ordersDisplay($serializer, $orders);

            return $this->json($dataOrders, Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la récupération des commandes des clients: ', [$e->getMessage()]);
            return $this->json(['erreur' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
