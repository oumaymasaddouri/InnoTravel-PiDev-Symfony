<?php

namespace App\Entity;

use App\Repository\TransportRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: TransportRepository::class)]
#[Assert\Callback(callback: [self::class, 'validateLicensePlate'])]
class Transport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Vehicle type is required.')]
    #[Assert\Choice(
        choices: ['car', 'taxi', 'minibus', 'truck'],
        message: 'Choose a valid vehicle type: car, taxi, minibus, or truck.'
    )]
    private ?string $vehicleType = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Car model is required.')]
    private ?string $carModel = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Status is required.')]
    #[Assert\Choice(choices: ['Active', 'Inactive'])]
    private ?string $status = 'Active';

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Car color is required.')]
    private ?string $carColor = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'License plate is required.')]
    #[Assert\Regex(
        pattern: '/^([0-9]{1,3})tunis([0-9]{1,4})$/',
        message: 'License plate must follow the format: number (max 250) + "tunis" + number (max 9999). Example: 154tunis1220.'
    )]
    private ?string $licensePlate = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Choice(
        choices: [0, 1, 2, 3, 4, 5],
        message: 'Max luggage must be between 0 and 5.'
    )]
    private ?int $maxLuggage = null;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'transport', cascade: ['remove'])]
    private Collection $reservations;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageFilename = null;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVehicleType(): ?string
    {
        return $this->vehicleType;
    }

    public function setVehicleType(string $vehicleType): static
    {
        $this->vehicleType = $vehicleType;
        return $this;
    }

    public function getCarModel(): ?string
    {
        return $this->carModel;
    }

    public function setCarModel(string $carModel): static
    {
        $this->carModel = $carModel;
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

    public function getCarColor(): ?string
    {
        return $this->carColor;
    }

    public function setCarColor(string $carColor): static
    {
        $this->carColor = $carColor;
        return $this;
    }

    public function getLicensePlate(): ?string
    {
        return $this->licensePlate;
    }

    public function setLicensePlate(string $licensePlate): static
    {
        $this->licensePlate = $licensePlate;
        return $this;
    }

    public function getMaxLuggage(): ?int
    {
        return $this->maxLuggage;
    }

    public function setMaxLuggage(?int $maxLuggage): static
    {
        $this->maxLuggage = $maxLuggage;
        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setTransport($this);
        }
        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            if ($reservation->getTransport() === $this) {
                $reservation->setTransport(null);
            }
        }
        return $this;
    }

    public function getImageFilename(): ?string
    {
        return $this->imageFilename;
    }

    public function setImageFilename(?string $imageFilename): static
    {
        $this->imageFilename = $imageFilename;
        return $this;
    }

    // Custom license plate validation logic
    public static function validateLicensePlate(self $transport, ExecutionContextInterface $context): void
    {
        $license = $transport->getLicensePlate();

        if ($license && preg_match('/^(\d{1,3})tunis(\d{1,4})$/', $license, $matches)) {
            $first = (int) $matches[1];
            $second = (int) $matches[2];

            if ($first > 250) {
                $context->buildViolation('The first number must not exceed 250.')
                    ->atPath('licensePlate')
                    ->addViolation();
            }

            if ($second > 9999) {
                $context->buildViolation('The second number must not exceed 9999.')
                    ->atPath('licensePlate')
                    ->addViolation();
            }
        }
    }
}
