<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

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

    #[Route('/existed', methods: ['POST'])]
    public function exist(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $userExisted = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            if (!$userExisted) {
                return $this->json(['type' => 'ERROR_USER_EXISTING', 'message' => 'Aucun compte ne correspond à cet email'], Response::HTTP_CONFLICT);
            }

            return $this->json(null, Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la vérification login : ', [$e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/register', methods: ['POST'])]
    public function register(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $userExisted = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            if ($userExisted) {
                return $this->json(['type' => 'ERROR_USER_EXISTING', 'message' => 'Un compte existe déjà avec cet email'], Response::HTTP_CONFLICT);
            }

            $user = new User();

            $form = $this->createForm(UserType::class, $user);
            $form->submit($data, false);

            if (!$form->isSubmitted() || !$form->isValid()) {
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
