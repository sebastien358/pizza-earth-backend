<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/reviews')]
class ReviewController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/add', methods: ['POST'])]
    public function add(Request $request): Response
    {
        try {
            $data = $request->files->all() ?? null;
            dd($data);
        } catch (\Throwable $e) {
            $this->logger->error('error de l\'jout d\'un avis : ', [$e->getMessage()]);
            return $this->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
