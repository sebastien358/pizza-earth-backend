<?php

namespace App\Controller\User;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Form\OrderType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/user/orders')]
#[IsGranted('ROLE_USER')]
class OrderController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    #[Route('/new', methods: ['POST'])]
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
            $order->setStatus(Order::STATUS_PENDING);

            $this->entityManager->persist($order);
            $this->entityManager->flush();

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
