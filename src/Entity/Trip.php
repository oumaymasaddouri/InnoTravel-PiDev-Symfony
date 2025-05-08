<?php

namespace App\Entity;

use App\Repository\TripRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: TripRepository::class)]
class Trip
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank]
    #[Assert\Type(\DateTimeInterface::class)]
    private ?\DateTimeInterface $arrivalDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank]
    #[Assert\Type(\DateTimeInterface::class)]
    #[Assert\LessThan(propertyPath: "arrivalDate", message: "Departure must be before arrival.")]
    private ?\DateTimeInterface $departure = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $budget = null;

    #[ORM\ManyToOne(inversedBy: 'trips')]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    /**
     * @var Collection<int, RelationTripItineraire>
     */
    #[ORM\OneToMany(targetEntity: RelationTripItineraire::class, mappedBy: 'Trip')]
    private Collection $relationTripItineraires;

    public function __construct()
    {
        $this->relationTripItineraires = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArrivalDate(): ?\DateTimeInterface
    {
        return $this->arrivalDate;
    }

    public function setArrivalDate(\DateTimeInterface $arrivalDate): static
    {
        $this->arrivalDate = $arrivalDate;

        return $this;
    }

    public function getDeparture(): ?\DateTimeInterface
    {
        return $this->departure;
    }

    public function setDeparture(\DateTimeInterface $departure): static
    {
        $this->departure = $departure;

        return $this;
    }

    public function getBudget(): ?string
    {
        return $this->budget;
    }

    public function setBudget(string $budget): static
    {
        $this->budget = $budget;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, RelationTripItineraire>
     */
    public function getRelationTripItineraires(): Collection
    {
        return $this->relationTripItineraires;
    }

    public function addRelationTripItineraire(RelationTripItineraire $relationTripItineraire): static
    {
        if (!$this->relationTripItineraires->contains($relationTripItineraire)) {
            $this->relationTripItineraires->add($relationTripItineraire);
            $relationTripItineraire->setTrip($this);
        }

        return $this;
    }

    public function removeRelationTripItineraire(RelationTripItineraire $relationTripItineraire): static
    {
        if ($this->relationTripItineraires->removeElement($relationTripItineraire)) {
            // set the owning side to null (unless already changed)
            if ($relationTripItineraire->getTrip() === $this) {
                $relationTripItineraire->setTrip(null);
            }
        }

        return $this;
    }
}
