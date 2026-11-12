<?php

namespace App\Services;

use App\Entity\Picture;
use Doctrine\ORM\EntityManagerInterface;

class ProductService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function ProductImage($product, $image)
    {
        if (!$image) return null;

        if ($image->getSize() > 5 * 1024 * 1024) {
            throw new \Exception('La taille de l\'image est trop grande'. $image->getClientOriginalName());
        }

        $picture = new Picture();

        $picture->setUrl($image);
        $picture->setProduct($product);
        $product->addPicture($picture);
        $this->entityManager->persist($picture);
    }
}
