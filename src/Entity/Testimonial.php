<?php

namespace App\Entity;

use App\Repository\TestimonialRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: TestimonialRepository::class)]
class Testimonial
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['testimonials', 'testimonial'])]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    #[Groups(['testimonials', 'testimonial'])]
    private ?int $rating = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['testimonials', 'testimonial'])]
    private ?string $name;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['testimonials', 'testimonial'])]
    private ?string $pizza;

    #[ORM\Column(type: 'text')]
    #[Groups(['testimonials', 'testimonial'])]
    private ?string $message;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['testimonials', 'testimonial'])]
    private ?bool $isPublished = false;

    #[ORM\OneToMany(targetEntity: Picture::class, mappedBy: 'testimonial', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['testimonials', 'testimonial'])]
    private Collection $pictures;

    public function __construct()
    {
        $this->pictures= new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(?int $rating): self
    {
        $this->rating = $rating;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPizza(): ?string
    {
        return $this->pizza;
    }

    public function setPizza(string $pizza): self
    {
        $this->pizza = $pizza;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getIsPublished(): ?bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): self
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function getPictures(): Collection
    {
        return $this->pictures;
    }

    public function addPicture(Picture $picture): self
    {
        if (!$this->pictures->contains($picture)) {
            $this->pictures[] = $picture;
            $picture->setTestimonial($this);
        }
        return $this;
    }

    public function removePicture(Picture $picture): self
    {
        if ($this->pictures->contains($picture)) {
            $this->pictures->removeElement($picture);
            if ($picture->getTestimonial() === $this) {
                $picture->setTestimonial(null);
            }
        }
        return $this;
    }
}
