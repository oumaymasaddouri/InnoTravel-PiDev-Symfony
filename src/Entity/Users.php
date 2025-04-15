<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use App\Entity\Booking;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Users
{

    #[ORM\Id]  
    #[ORM\GeneratedValue]

    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Please enter a valid email address')]
    private string $email;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: 'Password is required')]
    #[Assert\Length(
        min: 6,
        max: 50,
        minMessage: 'Password must be at least {{ limit }} characters long',
        maxMessage: 'Password cannot be longer than {{ limit }} characters'
    )]
    private string $password;

    #[ORM\Column(type: "string")]
    #[Assert\NotBlank(message: 'Role is required')]
    #[Assert\Choice(
        choices: ['ROLE_USER', 'ROLE_ADMIN'],
        message: 'Please select a valid role'
    )]
    private string $role;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($value)
    {
        $this->email = $value;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($value)
    {
        $this->password = $value;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function setRole($value)
    {
        $this->role = $value;
    }

    #[ORM\OneToMany(mappedBy: "userId", targetEntity: Booking::class)]
    private Collection $bookings;

        public function getBookings(): Collection
        {
            return $this->bookings;
        }
    
        public function addBooking(Booking $booking): self
        {
            if (!$this->bookings->contains($booking)) {
                $this->bookings[] = $booking;
                $booking->setUserId($this);
            }
    
            return $this;
        }
    
        public function removeBooking(Booking $booking): self
        {
            if ($this->bookings->removeElement($booking)) {
                // set the owning side to null (unless already changed)
                if ($booking->getUserId() === $this) {
                    $booking->setUserId(null);
                }
            }
    
            return $this;
        }
}
