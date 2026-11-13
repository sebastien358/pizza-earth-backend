<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadFileService
{
    private $targetDirectory;
    private EntityManagerInterface $entityManager;

    public function __construct(string $targetDirectory, EntityManagerInterface $entityManager)
    {
        $this->targetDirectory = $targetDirectory;
        $this->entityManager = $entityManager;
    }

    public function upload(UploadedFile $uploadedFile)
    {
        $filename = uniqid() . '.' . $uploadedFile->guessExtension();
        $uploadedFile->move($this->targetDirectory, $filename);
        return $filename;
    }

    public function deleteImage($product, $image)
    {
        if (!$image) return;

        $filename = $this->targetDirectory . '/' . $image->getUrl();

        if (file_exists($filename)) {
            unlink($filename);
        }

        $this->entityManager->remove($product);
    }
}
