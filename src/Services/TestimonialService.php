<?php

namespace App\Services;

use App\Entity\Picture;
use App\Entity\Testimonial;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class TestimonialService
{
    private UploadFileService $uploadFileService;
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;

    public function __construct(UploadFileService $uploadFileService, EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $this->uploadFileService = $uploadFileService;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    public function testimonialDisplay($request, $testimonials)
    {
        if (is_array($testimonials)) {
            $elems = ['groups' => ['testimonials', 'picture'], 'circular_reference_handler' => function ($object) {
                return $object->getId();
            }];

            $dataTestimonials = $this->serializer->normalize($testimonials, 'json' , $elems);

            $urlImage = $request->getSchemeAndHttpHost() . '/images/';
            foreach ($dataTestimonials as &$testimonial) {
               if (isset($testimonial['pictures'])) {
                   foreach ($testimonial['pictures'] as &$picture) {
                       $picture['url'] = $urlImage . basename($picture['url']);
                   }

                   unset($picture);
               }
            }
            unset($testimonials);
            return $dataTestimonials;
        }
        return [];
    }

    public function testimonialImage($testimonial, $image)
    {
        if (!$image) return null;

        if ($image->getSize() > 2 * 1024 * 1024) {
            throw new \Exception('Maximum image size is 2 MB');
        }

        $newFile = $this->uploadFileService->upload($image);

        $picture = new Picture();
        $picture->setUrl($newFile);
        $picture->setTestimonial($testimonial);
        $this->entityManager->persist($picture);
    }
}
