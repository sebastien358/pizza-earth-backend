<?php

namespace App\Services;

use App\Entity\Picture;
use Doctrine\ORM\EntityManagerInterface;

class ProductService
{
    private EntityManagerInterface $entityManager;
    private UploadFileService $uploadFileService;

    public function __construct(EntityManagerInterface $entityManager, UploadFileService $uploadFileService)
    {
        $this->entityManager = $entityManager;
        $this->uploadFileService = $uploadFileService;
    }

    public function productDisplay($request, $serializer, $products) {

        if (is_array($products)) {
            $dataProducts = $serializer->normalize($products, 'json', ['groups' => ['products', 'category', 'picture'],
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);

            $urlImage = $request->getSchemeAndHttpHost() . '/images/';

            foreach ($dataProducts as &$product) {
                if (isset($product['pictures'])) {
                    foreach ($product['pictures'] as &$picture) {
                        $picture['url'] = $urlImage . basename($picture['url']);
                    }
                }
            }
            unset($product);
            return $dataProducts;
        } else {
            $dataProducts = $serializer->normalize($products, 'json', ['groups' => ['products', 'category', 'picture'],
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);

            $urlImage = $request->getSchemeAndHttpHost() . '/images/';

            if (isset($dataProducts['pictures'])) {
                foreach ($dataProducts['pictures'] as &$picture) {
                    $picture['url'] = $urlImage . basename($picture['url']);
                }
            }
            return $dataProducts;
        }
    }

    public function ProductImage($product, $image)
    {
        if (!$image) return null;

        if ($image->getSize() > 5 * 1024 * 1024) {
            throw new \Exception('La taille de l\'image est trop grande'. $image->getClientOriginalName());
        }

        $img = $this->uploadFileService->upload($image);

        $picture = new Picture();
        $picture->setUrl($img);
        $picture->setProduct($product);
        $product->addPicture($picture);
        $this->entityManager->persist($picture);
    }
}
