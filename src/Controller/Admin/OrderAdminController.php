<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Services\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
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
    public function index(#[MapQueryParameter(name: 'current')] int $current = 1, #[MapQueryParameter(name: 'limit')] int $limit = 4, SerializerInterface $serializer): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['erreur' => 'Utilisateur introuvable'], Response::HTTP_UNAUTHORIZED);
            }

            if ($current < 1) $current = 1;
            if ($limit <= 0) $limit = 4;

            $orders = $this->entityManager->getRepository(Order::class)->findAllAdminOrders($current, $limit);
            $dataOrders = $this->orderService->ordersDisplay($orders, $serializer);

            $count = $this->entityManager->getRepository(Order::class)->countOrdersAdmin();

            return $this->json([
                'orders' => $dataOrders,
                'count' => (int) $count,
                'pages' => (int) ceil($count / $limit)
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la récupération des commandes des clients: ', [$e->getMessage()]);
            return $this->json(['erreur' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/orders/search', methods: ['GET'])]
    public function search(#[MapQueryParameter(name: 'search')] string $search = "", SerializerInterface $serializer, Request $request): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['erreur' => 'Utilisateur introuvable'], Response::HTTP_UNAUTHORIZED);
            }

            $searchParams = strtolower($search);

            $orders = $this->entityManager->getRepository(Order::class)->findAllAdminOrdersSearch($searchParams);
            $dataOrders = $this->orderService->ordersDisplay($orders, $serializer);

            return $this->json(['orders' => $dataOrders], Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la récupération des commandes des clients: ', [$e->getMessage()]);
            return $this->json(['erreur' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/orders/{id}/delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['erreur' => 'Utilisateur introuvable'], Response::HTTP_UNAUTHORIZED);
            }

            $order = $this->entityManager->getRepository(Order::class)->find($id);
            if (!$order) {
                return $this->json(['error' => 'Aucune commandes'], Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($order);
            $this->entityManager->flush();

            return $this->json(['message' => 'Le status a été modifié'], Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la suppression de la commande : ', [$e->getMessage()]);
            return $this->json(['erreur' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/orders/{id}/status/{status}', methods: ['PATCH'])]
    public function status(int $id, string $status): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['erreur' => 'Utilisateur introuvable'], Response::HTTP_UNAUTHORIZED);
            }

            $order = $this->entityManager->getRepository(Order::class)->find($id);
            if (!$order) {
                return $this->json(['error' => 'Aucune commandes'], Response::HTTP_NOT_FOUND);
            }

            $newStatus = OrderStatus::tryFrom($status);
            if (!$newStatus) {
                return $this->json(['error' => 'Status de commande invalide'], Response::HTTP_BAD_REQUEST);
            }

            $order->setStatus($newStatus);
            $this->entityManager->flush();

            return $this->json(['message' => 'Le status a été modifié'], Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur du changement de status de la commande : ', [$e->getMessage()]);
            return $this->json(['erreur' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
