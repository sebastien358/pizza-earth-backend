<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Form\ProductType;
use App\Services\ProductService;
use App\Services\UploadFileService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;


#[Route('/api/admin/products')]
class ProductAdminController extends AbstractController
{
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private ProductService $productService;
    private UploadFileService $uploadFileService;

    public function __construct(
        LoggerInterface $logger, EntityManagerInterface $entityManager, ProductService $productService, UploadFileService $uploadFileService)
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->productService = $productService;
        $this->uploadFileService = $uploadFileService;
    }

    #[Route('/list', methods: ['GET'])]
    public function index(Request $request, SerializerInterface $serializer): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_UNAUTHORIZED);
            }

            $products = $this->entityManager->getRepository(Product::class)->findAll();
            $dataProducts = $this->productService->productDisplay($request, $serializer, $products);

            $countProducts = $this->entityManager->getRepository(Product::class)->countProducts();

            return $this->json([
                'products' => $dataProducts,
                'count' => (int) $countProducts
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la récupération des produits : ', [$e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/search', methods: ['GET'])]
    public function search(Request $request, SerializerInterface $serializer): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_UNAUTHORIZED);
            }

            $search = $request->query->getString('search');

            $products = $this->entityManager->getRepository(Product::class)->findAllSearch($search);
            $dataProducts = $this->productService->productDisplay($request, $serializer, $products);

            return $this->json($dataProducts, Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la récupération des produits : ', [$e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', methods: ['GET'])]
    public function id(int $id, Request $request, SerializerInterface $serializer): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_UNAUTHORIZED);
            }

            $products = $this->entityManager->getRepository(Product::class)->find($id);
            if (!$products) {
                return $this->json(['error' => 'Produit introuvable'], Response::HTTP_NOT_FOUND);
            }

            $dataProducts = $this->productService->productDisplay($request, $serializer, $products);

            return $this->json($dataProducts, Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la récupération des produits : ', [$e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/published', methods: ['PATCH'])]
    public function published(int $id): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_UNAUTHORIZED);
            }

            $product = $this->entityManager->getRepository(Product::class)->find($id);
            if (!$product) {
                return $this->json(['error' => 'Produit introuvable'], Response::HTTP_NOT_FOUND);
            }

            $product->setIsVisible(!$product->getIsVisible());
            $this->entityManager->flush();

            return $this->json([
                'message' => $product->getIsVisible() ? 'Produit en ligne' : 'Produit en brouillon',
                'isVisible' => $product->getIsVisible(),
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la publication : ' . $e->getMessage());
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/new', methods: ['POST'])]
    public function new(Request $request): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_UNAUTHORIZED);
            }

            $product = new Product();

            $form = $this->createForm(ProductType::class, $product);
            $data = $request->request->all();
            $form->submit($data, false);

            if (!$form->isSubmitted() && !$form->isValid()) {
                $errors = $this->getErrorMessages($form);
                return $this->json(['error' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $image = $request->files->get('picture') ?? null;
            $this->productService->ProductImage($product, $image);

            $this->entityManager->persist($product);
            $this->entityManager->flush();

            return $this->json(['message' => 'Le produit a été ajouté'], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur de l\'ajout d\'un produit : ', [$e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_UNAUTHORIZED);
            }

            $product = $this->entityManager->getRepository(Product::class)->find($id);
            if (!$product) {
                return $this->json(['error' => 'Produit introuvable'], Response::HTTP_NOT_FOUND);
            }

            $image = $product->getPictures()->first();
            $this->uploadFileService->deleteProduct($product, $image);

            $this->entityManager->flush();

            return $this->json(['Le produit a été supprimé'], Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la suppression d\'un produit : ', [$e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/image', methods: ['DELETE'])]
    public function deleteImage(int $id): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_UNAUTHORIZED);
            }

            $product = $this->entityManager->getRepository(Product::class)->find($id);
            if (!$product) {
                return $this->json(['error' => 'Produit introuvable'], Response::HTTP_NOT_FOUND);
            }

            $image = $product->getPictures()->first();
            $this->uploadFileService->deleteImage($image);

            $this->entityManager->flush();

            return $this->json(['Le produit a été supprimé'], Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la suppression d\'un produit : ', [$e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/edit', methods: ['PATCH'])]
    public function edit(int $id, Request $request): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_UNAUTHORIZED);
            }

            $product = $this->entityManager->getRepository(Product::class)->find($id);
            if (!$product) {
                return $this->json(['error' => 'Produit introuvable'], Response::HTTP_NOT_FOUND);
            }

            $form = $this->createForm(ProductType::class, $product);
            $data = $request->request->all();
            $form->submit($data);

            if (!$form->isSubmitted() && !$form->isValid()) {
                $errors = $this->getErrorMessages($form);
                return $this->json(['error' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $image = $request->files->get('picture') ?? null;
            $this->productService->ProductImage($product, $image);

            $this->entityManager->flush();

            return $this->json(['message' => 'Le produit a été modifié'], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur la modification d\'un produit : ', [$e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/edit/image', methods: ['PATCH'])]
    public function editImage(int $id, Request $request): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_UNAUTHORIZED);
            }

            $product = $this->entityManager->getRepository(Product::class)->find($id);
            if (!$product) {
                return $this->json(['error' => 'Produit introuvable'], Response::HTTP_NOT_FOUND);
            }

            $image = $request->files->get('picture') ?? null;
            $this->productService->ProductImage($product, $image);

            $this->entityManager->flush();

            return $this->json(['message' => 'Le produit a été modifié'], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur la modification d\'un produit : ', [$e->getMessage()]);
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
