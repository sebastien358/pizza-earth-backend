<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api')]
final class UserController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->logger = $logger;
    }

    #[Route('/register', methods: ['POST'])]
    public function register(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $user = new User();

            $form = $this->createForm(UserType::class, $user);
            $form->submit($data, false);

            if (!$form->isSubmitted() && !$form->isValid()) {
                $errors = $this->getErrorMessages($form);
                return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $hasher = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hasher);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->json(['message' => 'L\'inscription a réussie'], Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur de l\'inscription : ', [$e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
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
