<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    private ?Transport $transport = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Pickup address is required.')]
    private ?string $pickup_address = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Destination address is required.')]
    private ?string $destination_address = null;

    #[ORM\Column(nullable: true)]
    private ?float $price = null;

    #[ORM\Column(length: 255)]
    #[Assert\Choice(choices: ['pending', 'confirmed', 'canceled'], message: 'Invalid status.')]
    private ?string $status = 'pending';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $estimatedDuration = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $specialRequests = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pickupLatitude = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pickupLongitude = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $destinationLatitude = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $destinationLongitude = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $reservationDate = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $reservationTime = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTime();
        }

        if ($this->status === null) {
            $this->status = 'pending';
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTransport(): ?Transport
    {
        return $this->transport;
    }

    public function setTransport(?Transport $transport): static
    {
        $this->transport = $transport;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getPickupAddress(): ?string
    {
        return $this->pickup_address;
    }

    public function setPickupAddress(string $pickup_address): static
    {
        $this->pickup_address = $pickup_address;
        return $this;
    }

    public function getDestinationAddress(): ?string
    {
        return $this->destination_address;
    }

    public function setDestinationAddress(string $destination_address): static
    {
        $this->destination_address = $destination_address;
        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getEstimatedDuration(): ?int
    {
        return $this->estimatedDuration;
    }

    public function setEstimatedDuration(?int $estimatedDuration): static
    {
        $this->estimatedDuration = $estimatedDuration;
        return $this;
    }

    public function getSpecialRequests(): ?string
    {
        return $this->specialRequests;
    }

    public function setSpecialRequests(?string $specialRequests): static
    {
        $this->specialRequests = $specialRequests;
        return $this;
    }

    public function getPickupLatitude(): ?float
    {
        return $this->pickupLatitude;
    }

    public function setPickupLatitude(?float $pickupLatitude): self
    {
        $this->pickupLatitude = $pickupLatitude;
        return $this;
    }

    public function getPickupLongitude(): ?float
    {
        return $this->pickupLongitude;
    }

    public function setPickupLongitude(?float $pickupLongitude): self
    {
        $this->pickupLongitude = $pickupLongitude;
        return $this;
    }

    public function getDestinationLatitude(): ?float
    {
        return $this->destinationLatitude;
    }

    public function setDestinationLatitude(?float $destinationLatitude): self
    {
        $this->destinationLatitude = $destinationLatitude;
        return $this;
    }

    public function getDestinationLongitude(): ?float
    {
        return $this->destinationLongitude;
    }

    public function setDestinationLongitude(?float $destinationLongitude): self
    {
        $this->destinationLongitude = $destinationLongitude;
        return $this;
    }

    public function getReservationDate(): ?\DateTimeInterface
    {
        return $this->reservationDate;
    }

    public function setReservationDate(?\DateTimeInterface $reservationDate): static
    {
        $this->reservationDate = $reservationDate;
        return $this;
    }

    public function getReservationTime(): ?\DateTimeInterface
    {
        return $this->reservationTime;
    }

    public function setReservationTime(?\DateTimeInterface $reservationTime): static
    {
        $this->reservationTime = $reservationTime;
        return $this;
    }
}
