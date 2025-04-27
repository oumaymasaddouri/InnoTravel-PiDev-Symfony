<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use App\Entity\Booking;
use App\Repository\HotelRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: HotelRepository::class)]
class Hotel
{

    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: 'Hotel name is required')]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: 'Hotel name must be at least {{ limit }} characters long',
        maxMessage: 'Hotel name cannot be longer than {{ limit }} characters'
    )]
    private string $name;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: 'Location is required')]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: 'Location must be at least {{ limit }} characters long',
        maxMessage: 'Location cannot be longer than {{ limit }} characters'
    )]
    private string $location;

    #[ORM\Column(type: "float")]
    #[Assert\NotBlank(message: 'Price per night is required')]
    #[Assert\Type(type: 'numeric', message: 'Price must be a number')]
    #[Assert\Range(
        min: 0,
        max: 9999.99,
        notInRangeMessage: 'Price must be between {{ min }} and {{ max }}'
    )]
    private float $pricepernight;

    #[ORM\Column(type: "float")]
    #[Assert\NotBlank(message: 'Rating is required')]
    #[Assert\Type(type: 'numeric', message: 'Rating must be a number')]
    #[Assert\Range(
        min: 0,
        max: 5,
        notInRangeMessage: 'Rating must be between {{ min }} and {{ max }}'
    )]
    private float $rating;

    #[ORM\Column(type: "text")]
    #[Assert\NotBlank(message: 'Description is required')]
    #[Assert\Length(
        min: 10,
        max: 1000,
        minMessage: 'Description must be at least {{ limit }} characters long',
        maxMessage: 'Description cannot be longer than {{ limit }} characters'
    )]
    private string $description;

    #[ORM\Column(type: "boolean")]
    #[Assert\NotNull(message: 'Eco certification must be selected')]
    private bool $ecocertification;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: "string", length: 255, unique: true)]
    #[Gedmo\Slug(fields: ['name'])]
    private string $slug;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($value)
    {
        $this->name = $value;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function setLocation($value)
    {
        $this->location = $value;
    }

    public function getPricepernight()
    {
        return $this->pricepernight;
    }

    public function setPricepernight($value)
    {
        $this->pricepernight = $value;
    }

    public function getRating()
    {
        return $this->rating;
    }

    public function setRating($value)
    {
        $this->rating = $value;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($value)
    {
        $this->description = $value;
    }

    public function getEcocertification()
    {
        return $this->ecocertification;
    }

    public function setEcocertification($value)
    {
        $this->ecocertification = $value;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    #[ORM\OneToMany(mappedBy: "hotelId", targetEntity: Booking::class)]
    private Collection $bookings;

        public function getBookings(): Collection
        {
            return $this->bookings;
        }

        public function addBooking(Booking $booking): self
        {
            if (!$this->bookings->contains($booking)) {
                $this->bookings[] = $booking;
                $booking->setHotelId($this);
            }

            return $this;
        }

        public function removeBooking(Booking $booking): self
        {
            if ($this->bookings->removeElement($booking)) {
                // set the owning side to null (unless already changed)
                if ($booking->getHotelId() === $this) {
                    $booking->setHotelId(null);
                }
            }

            return $this;
        }
}
