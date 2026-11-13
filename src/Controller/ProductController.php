<?php

namespace App\Controller;

use App\Entity\Product;
use App\Services\ProductService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/products')]
class ProductController extends AbstractController
{
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private ProductService $productService;

    public function __construct(
        LoggerInterface $logger, EntityManagerInterface $entityManager, ProductService $productService)
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->productService = $productService;
    }

    #[Route('/home/list', methods: ['GET'])]
    public function home(Request $request, SerializerInterface $serializer): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_UNAUTHORIZED);
            }

            $products = $this->entityManager->getRepository(Product::class)->findAllHomePublished();
            $dataProducts = $this->productService->productDisplay($request, $serializer, $products);

            return $this->json($dataProducts, Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la récupération des produits : ', [$e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/list', methods: ['GET'])]
    public function index(Request $request, SerializerInterface $serializer): Response
    {
        try {
            $products = $this->entityManager->getRepository(Product::class)->findAllPublished();
            $dataProducts = $this->productService->productDisplay($request, $serializer, $products);

            return $this->json($dataProducts, Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la récupération des produits : ', [$e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
