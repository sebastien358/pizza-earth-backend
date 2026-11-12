<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Form\ProductType;
use App\Services\ProductService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/api/admin/products')]
class ProductAdminController extends AbstractController
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
            $form->submit($data);

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
