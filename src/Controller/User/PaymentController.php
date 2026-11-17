<?php

namespace App\Controller\User;

use Psr\Log\LoggerInterface;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/user')]
#[IsGranted('ROLE_USER')]
class PaymentController extends AbstractController
{
    private LoggerInterface $logger;
    private string $stripeKey;
    private StripeClient $stripe;

    public function __construct(LoggerInterface $logger, string $stripeKey)
    {
        $this->logger = $logger;
        $this->stripeKey = $stripeKey;
        $this->stripe = new StripeClient($this->stripeKey);
    }

    #[Route('/payment', name: 'user_payment', methods: ['POST'])]
    public function payment(Request $request): Response
    {
        $paymentIntent = null;

        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'User not found'], Response::HTTP_UNAUTHORIZED);
            }

            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            // 1. Validation des données
            if (empty($data['id']) || empty($data['amount']) || $data['amount'] <= 0) {
                return $this->json(['error' => 'Données de paiement invalides'], Response::HTTP_BAD_REQUEST);
            }

            $paymentMethodId = $data['id'];
            $amount = (int) $data['amount'];
            $currency = $data['currency'] ?? 'eur';

            // 2. Anti-doublon : même paymentMethod + même user = même clé
            // Si le client double-clique, Stripe ne crée qu'un seul paiement
            $idempotencyKey = 'pi_user_' . $user->getId() . '_' . $paymentMethodId;

            // 3. Customer
            $customer = $this->stripe->customers->create([
                'email' => $user->getEmail(),
                'name' => trim($user->getFirstName() . ' ' . $user->getLastName()),
                'metadata' => ['app_user_id' => (string) $user->getId()],
            ]);

            // 4. PaymentIntent
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $amount,
                'currency' => $currency,
                'customer' => $customer->id,
                'payment_method' => $paymentMethodId,
                'confirm' => true,
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'never',
                ],
                'metadata' => [
                    'user_id' => (string) $user->getId(),
                    'payment_method_id' => $paymentMethodId,
                ],
            ], [
                'idempotency_key' => $idempotencyKey,
            ]);

            // 5. Succès
            if ($paymentIntent->status === 'succeeded') {
                return $this->json([
                    'type' => 'SUCCESS_PAYMENT',
                    'message' => 'Paiement accepté',
                    'payment_intent_id' => $paymentIntent->id,
                ], Response::HTTP_CREATED);
            }

            // Paiement créé mais pas confirmé (3D Secure, etc.)
            return $this->json([
                'type' => 'REQUIRES_ACTION',
                'message' => 'Action requise',
                'payment_intent_id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'client_secret' => $paymentIntent->client_secret,
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            $this->logger->error('Erreur du paiement : ' . $e->getMessage());

            // 6. Remboursement si le paiement est passé mais que ça a planté après
            if ($paymentIntent && $paymentIntent->status === 'succeeded') {
                try {
                    $this->stripe->refunds->create([
                        'payment_intent' => $paymentIntent->id,
                    ]);
                } catch (\Throwable $refundError) {
                    $this->logger->error('Erreur remboursement : ' . $refundError->getMessage());
                }
            }

            return $this->json([
                'type' => 'ERROR_PAYMENT',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
