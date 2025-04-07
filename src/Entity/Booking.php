<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

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
    private Users $userId;

        #[ORM\ManyToOne(targetEntity: Hotel::class, inversedBy: "bookings")]
    #[ORM\JoinColumn(name: 'hotelId', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Hotel $hotelId;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $startdate;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $enddate;

    #[ORM\Column(type: "string", length: 50)]
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
