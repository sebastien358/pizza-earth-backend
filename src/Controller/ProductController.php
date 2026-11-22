<?php

namespace App\Controller;

use App\Entity\Product;
use App\Services\ProductService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

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
    public function home(#[MapQueryParameter(name: 'limit')] int $limit = 4, Request $request): Response
    {
        try {
            if ($limit <= 0) {
                $limit = 4;
            }
            $products = $this->entityManager->getRepository(Product::class)->findAllHomePublished($limit);
            $dataProducts = $this->productService->productDisplay($products, $request);

            return $this->json($dataProducts, Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la récupération des produits : ', [$e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/list', methods: ['GET'])]
    public function index(#[MapQueryParameter(name: 'offset')] int $offset = 0, #[MapQueryParameter(name: 'limit')] int $limit = 4, Request $request): Response {
        try {
            if ($limit <= 0) {
                $limit = 4;
            }
            $products = $this->entityManager->getRepository(Product::class)->findAllPublished($offset, $limit);
            $dataProducts = $this->productService->productDisplay($products, $request);

            return $this->json($dataProducts, Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la récupération des produits : ', [$e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
