<?php

namespace App\Controller\User;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Enum\OrderStatus;
use App\Services\MailerProvider;
use App\Services\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/user')]
#[IsGranted('ROLE_USER')]
class OrderController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private OrderService $orderService;
    private LoggerInterface $logger;
    private MailerProvider $mailerProvider;

    public function __construct(EntityManagerInterface $entityManager, OrderService $orderService, LoggerInterface $logger, MailerProvider $mailerProvider)
    {
        $this->entityManager = $entityManager;
        $this->orderService = $orderService;
        $this->logger = $logger;
        $this->mailerProvider = $mailerProvider;
    }

    #[Route('/orders', methods: ['GET'])]
    public function index(#[MapQueryParameter] int $current = 1, #[MapQueryParameter] int $limit = 4): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['erreur' => 'Utilisateur introuvable'], Response::HTTP_UNAUTHORIZED);
            }

            if ($current < 1) $current = 1;
            if ($limit <= 0) $limit = 2;

            $orders = $this->entityManager->getRepository(Order::class)->findAllUserOrders($user, $current, $limit);
            $dataOrders = $this->orderService->ordersDisplay($orders);

            $countOrders = $this->entityManager->getRepository(Order::class)->countOrderUser($user);

            return $this->json([
                'orders' =>  $dataOrders,
                'count' => (int) $countOrders,
                'pages' => (int) ceil($countOrders / $limit)
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la récupération des commandes: ', [$e->getMessage()]);
            return $this->json(['erreur' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/orders/new', methods: ['POST'])]
    public function new(Request $request): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['erreur' => 'Utilisateur introuvable'], Response::HTTP_UNAUTHORIZED);
            }

            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $formData = $data['data'] ?? [];
            $itemsData = $data['items'] ?? [];

            $order = new Order();

            $order->setUser($user);
            $order->setName($formData['name'] ?? '');
            $order->setEmail($formData['email'] ?? '');
            $order->setPhone($formData['phone'] ?? '');
            $order->setDeliveryMode($formData['deliveryMode'] ?? 'delivery');
            $order->setAddress($formData['address'] ?? null);
            $order->setComplementAddress($formData['complementAddress'] ?? null);
            $order->setPostalCode($formData['postalCode'] ?? null);
            $order->setCity($formData['city'] ?? null);
            $order->setDeliveryInstructions($formData['deliveryInstructions'] ?? null);
            $order->setPaymentIntentId($formData['payment_intent_id'] ?? null);

            $total = 0;
            foreach ($itemsData as $item) {
                $product = $this->entityManager->getRepository(Product::class)->find($item['productId']);
                if (!$product) {
                    return $this->json(['erreur' => 'Produit introuvable'], Response::HTTP_NOT_FOUND);
                }

                $orderItem = new OrderItem();
                $orderItem->setProduct($product);
                $orderItem->setQuantity($item['quantity']);
                $orderItem->setPrice((float) $product->getPrice());

                $order->addOrderItem($orderItem);

                $total += $item['quantity'] * (float) $product->getPrice();
            }

            $deliveryPrice = ($formData['deliveryMode'] ?? 'delivery') === 'delivery' ? 3.90 : 0;
            $totalPayment = $total + $deliveryPrice;

            $order->setTotal($totalPayment);
            $order->setStatus(OrderStatus::PENDING);

            $this->entityManager->persist($order);
            $this->entityManager->flush();

            $this->mailerProvider->orderEmailPizzeria($order);
            $this->mailerProvider->orderEmailClient($order);

            return $this->json(['message' => 'La commande a été validée'], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la commande : ', [$e->getMessage()]);
            return $this->json(['erreur' => 'Erreur de la commande'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getErrorMessages(FormInterface $form): array
    {
        $errors = [];
        foreach ($form->getErrors() as $key => $error) {
            $errors[] = $error->getMessage();
        }
        foreach ($form->all() as $child) {
            if ($child->isSubmitted() && !$child->isValid()) {
                $errors[$child->getName()] = $this->getErrorMessages($child);
            }
        }
        return $errors;
    }
}
