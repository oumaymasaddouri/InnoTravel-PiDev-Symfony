<?php

namespace App\Entity;

use App\Repository\RelationTripItineraireRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RelationTripItineraireRepository::class)]
class RelationTripItineraire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'relationTripItineraires')]
    private ?Trip $Trip = null;

    #[ORM\ManyToOne(inversedBy: 'relationTripItineraires')]
    private ?Itineraire $itineraire = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTrip(): ?Trip
    {
        return $this->Trip;
    }

    public function setTrip(?Trip $Trip): static
    {
        $this->Trip = $Trip;

        return $this;
    }

    public function getItineraire(): ?Itineraire
    {
        return $this->itineraire;
    }

    public function setItineraire(?Itineraire $itineraire): static
    {
        $this->itineraire = $itineraire;

        return $this;
    }
}
