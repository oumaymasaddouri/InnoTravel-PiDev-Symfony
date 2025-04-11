<?php

namespace App\Entity;

use App\Repository\ItineraireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: ItineraireRepository::class)]
class Itineraire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "The itinerary name is required.")]
    #[Assert\Length(max: 255, maxMessage: "Name must be at most {{ limit }} characters.")]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Day program cannot be empty.")]
    private ?string $dayProgram = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Activity is mandatory.")]
    private ?string $Activity = null;

    /**
     * @var Collection<int, RelationTripItineraire>
     */
    #[ORM\OneToMany(targetEntity: RelationTripItineraire::class, mappedBy: 'itineraire', cascade: ['remove'])] // Add cascade remove
    private Collection $relationTripItineraires;

    public function __construct()
    {
        $this->relationTripItineraires = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDayProgram(): ?string
    {
        return $this->dayProgram;
    }

    public function setDayProgram(string $dayProgram): static
    {
        $this->dayProgram = $dayProgram;

        return $this;
    }

    public function getActivity(): ?string
    {
        return $this->Activity;
    }

    public function setActivity(string $Activity): static
    {
        $this->Activity = $Activity;

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
            $relationTripItineraire->setItineraire($this);
        }

        return $this;
    }

    public function removeRelationTripItineraire(RelationTripItineraire $relationTripItineraire): static
    {
        if ($this->relationTripItineraires->removeElement($relationTripItineraire)) {
            // set the owning side to null (unless already changed)
            if ($relationTripItineraire->getItineraire() === $this) {
                $relationTripItineraire->setItineraire(null);
            }
        }

        return $this;
    }
}
