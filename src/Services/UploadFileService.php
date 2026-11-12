<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadFileService
{
    private $targetDirectory;

    public function __construct(string $targetDirectory)
    {
        $this->targetDirectory = $targetDirectory;
    }

    public function upload(UploadedFile $uploadedFile)
    {
        $filename = uniqid() . '.' . $uploadedFile->guessExtension();
        $uploadedFile->move($this->targetDirectory, $filename);
        return $filename;
    }
}
