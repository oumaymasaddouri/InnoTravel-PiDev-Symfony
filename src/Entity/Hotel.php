<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\Booking;

#[ORM\Entity]
class Hotel
{

    #[ORM\Id]   
    #[ORM\GeneratedValue]

    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 255)]
    private string $name;

    #[ORM\Column(type: "string", length: 255)]
    private string $location;

    #[ORM\Column(type: "float")]
    private float $pricepernight;

    #[ORM\Column(type: "float")]
    private float $rating;

    #[ORM\Column(type: "text")]
    private string $description;

    #[ORM\Column(type: "boolean")]
    private bool $ecocertification;

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
