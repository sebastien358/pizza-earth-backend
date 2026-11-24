<?php

namespace App\Controller\Admin;

use App\Entity\Testimonial;
use App\Services\TestimonialService;
use App\Services\UploadFileService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin')]
class TestimonialAdminController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private TestimonialService $testimonialService;
    private UploadFileService $uploadFileService;

    public function __construct(
        EntityManagerInterface $entityManager, LoggerInterface $logger,
        TestimonialService $testimonialService, UploadFileService $uploadFileService
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->testimonialService = $testimonialService;
        $this->uploadFileService = $uploadFileService;
    }

    #[Route('/testimonials', methods: ['GET'])]
    public function index(#[MapQueryParameter('current')] int $current = 1, #[MapQueryParameter('limit')] int $limit = 4, Request $request): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'User not found.'], Response::HTTP_UNAUTHORIZED);
            }

            if ($current < 1) $current = 1;
            if ($limit <= 0) $limit = 3;

            $testimonials = $this->entityManager->getRepository(Testimonial::class)->findAllTestimonialsAdmin($current, $limit);
            $dataTestimonials = $this->testimonialService->testimonialDisplay($request, $testimonials);

            $countTestimonials = $this->entityManager->getRepository(Testimonial::class)->countTestimonialsAdmin();
            $averageRating = $this->entityManager->getRepository(Testimonial::class)->getAverageRatingAdmin();

            return $this->json([
                'testimonials' => $dataTestimonials,
                'count' => (int) $countTestimonials,
                'averageRating' => (float) $averageRating,
                'pages' => (int) ceil($countTestimonials / $limit)
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            $this->logger->error('erreur de la récupérations des témoignages : ', [$e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/testimonials/search', methods: ['GET'])]
    public function search(#[MapQueryParameter('search')] string $search = "", Request $request): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'User not found.'], Response::HTTP_UNAUTHORIZED);
            }

            $searchTerm = strtolower(trim($search));
            $testimonials = $this->entityManager->getRepository(Testimonial::class)->testimonialSearchAdmin($searchTerm);

            $dataTestimonials = $this->testimonialService->testimonialDisplay($request, $testimonials);

            return $this->json($dataTestimonials, Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('erreur de la récupérations des témoignages : ', [$e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/testimonials/{id}/is-visible', methods: ['PATCH'])]
    public function visible(int $id): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'User not found.'], Response::HTTP_UNAUTHORIZED);
            }

            $testimonial = $this->entityManager->getRepository(Testimonial::class)->find($id);
            $testimonial->setIsPublished(!$testimonial->getIsPublished());

            $this->entityManager->flush();

            return $this->json([
                'isPublished' => $testimonial->getIsPublished()
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            $this->logger->error('erreur de la récupérations des témoignages : ', [$e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/testimonials/{id}/delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'User not found.'], Response::HTTP_UNAUTHORIZED);
            }

            $testimonial = $this->entityManager->getRepository(Testimonial::class)->find($id);
            $image = $testimonial->getPictures()->first();

            $this->uploadFileService->deleteImage($image);

            $this->entityManager->remove($testimonial);
            $this->entityManager->flush();

            return $this->json(['message' => 'Le témoignage a été supprimé'], Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('erreur de la récupérations des témoignages : ', [$e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
