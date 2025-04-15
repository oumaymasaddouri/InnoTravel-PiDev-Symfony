<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use App\Entity\Hotel;

#[ORM\Entity]
class Booking
{

    #[ORM\Id]
    #[ORM\GeneratedValue]

    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "bookings")]
    #[ORM\JoinColumn(name: 'userId', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Assert\NotBlank(message: 'User is required')]
    private Users $userId;

    #[ORM\ManyToOne(targetEntity: Hotel::class, inversedBy: "bookings")]
    #[ORM\JoinColumn(name: 'hotelId', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Assert\NotBlank(message: 'Hotel is required')]
    private Hotel $hotelId;

    #[ORM\Column(type: "date")]
    #[Assert\NotBlank(message: 'Check-in date is required')]
    #[Assert\GreaterThan('today', message: 'Check-in date must be in the future')]
    private \DateTimeInterface $startdate;

    #[ORM\Column(type: "date")]
    #[Assert\NotBlank(message: 'Check-out date is required')]
    #[Assert\GreaterThan(propertyPath: 'startdate', message: 'Check-out date must be after check-in date')]
    private \DateTimeInterface $enddate;

    #[ORM\Column(type: "string", length: 50)]
    #[Assert\NotBlank(message: 'Status is required')]
    #[Assert\Choice(
        choices: ['pending', 'confirmed', 'cancelled'],
        message: 'Status must be either pending, confirmed, or cancelled'
    )]
    private string $status;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId($value)
    {
        $this->userId = $value;
    }

    public function getHotelId()
    {
        return $this->hotelId;
    }

    public function setHotelId($value)
    {
        $this->hotelId = $value;
    }

    public function getStartdate()
    {
        return $this->startdate;
    }

    public function setStartdate($value)
    {
        $this->startdate = $value;
    }

    public function getEnddate()
    {
        return $this->enddate;
    }

    public function setEnddate($value)
    {
        $this->enddate = $value;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($value)
    {
        $this->status = $value;
    }
}
