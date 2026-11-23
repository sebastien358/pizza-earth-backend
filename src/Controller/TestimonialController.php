<?php

namespace App\Controller;

use App\Entity\Testimonial;
use App\Form\TestimonialType;
use App\Services\TestimonialService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api')]
class TestimonialController extends AbstractController
{
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private TestimonialService $testimonialService;

    public function __construct(LoggerInterface $logger, TestimonialService $testimonialService, EntityManagerInterface $entityManager)
    {
        $this->logger = $logger;
        $this->testimonialService = $testimonialService;
        $this->entityManager = $entityManager;
    }

    #[Route('/testimonials', methods: ['GET'])]
    public function index(#[MapQueryParameter('current')] int $current = 1, #[MapQueryParameter('limit')] int $limit = 3, Request $request): Response
    {
        try {
            if ($current < 1) $current = 1;
            if ($limit <= 0) $limit = 3;

            $testimonials = $this->entityManager->getRepository(Testimonial::class)->findAllTestimonials($current, $limit);
            $dataTestimonials = $this->testimonialService->testimonialDisplay($request, $testimonials);

            $countTestimonials = $this->entityManager->getRepository(Testimonial::class)->countPublishedTestimonials();
            $averageRating = $this->entityManager->getRepository(Testimonial::class)->getAverageRating();

            return $this->json([
                'testimonials' => $dataTestimonials,
                'count' => (int) $countTestimonials,
                'averageRating' => (float) $averageRating,
                'pages' => (int) ceil($countTestimonials / $limit)
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            $this->logger->error('error récupération des avis: ', [$e->getMessage()]);
            return $this->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/testimonials/add', methods: ['POST'])]
    public function add(Request $request): Response
    {
        try {
            $testimonial = new Testimonial();

            $form = $this->createForm(TestimonialType::class, $testimonial);
            $data = $request->request->all();
            $form->submit($data);

            if (!$form->isSubmitted() || !$form->isValid()) {
                $errors = $this->getErrorMessages($form);
                return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $image = $request->files->get('picture') ?? null;
            $this->testimonialService->testimonialImage($testimonial, $image);

            $this->entityManager->flush();

            return $this->json(['message' => 'L\'avis a été ajouté'], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            $this->logger->error('error de l\'jout d\'un avis : ', [$e->getMessage()]);
            return $this->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
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
