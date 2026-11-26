<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use App\Services\MailerProvider;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/contacts')]
class ContactController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private MailerProvider $mailerProvider;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, MailerProvider $mailerProvider, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->mailerProvider = $mailerProvider;
        $this->logger = $logger;
    }

    #[Route('/add', methods: ['POST'])]
    public function contactAdd(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $contact = new Contact();

            $form = $this->createForm(ContactType::class, $contact);
            $form->submit($data, false);

            if (!$form->isSubmitted() || !$form->isValid()) {
                $error = $this->getErrorMessages($form);
                return $this->json(['errors' => $error], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($contact);
            $this->entityManager->flush();

            $this->mailerProvider->contactEmail($contact);

            return $this->json(['message' => 'Le message a étét envoyé'], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            $this->logger->error('Error de l\'ajout d\'un message : ', [$e->getMessage()]);
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
