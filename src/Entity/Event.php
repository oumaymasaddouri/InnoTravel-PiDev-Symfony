<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Organizer;

#[ORM\Entity]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    private string $name;

    #[ORM\Column(type: "string", length: 255)]
    private string $description;

    #[ORM\Column(type: "string", length: 255)]
    private string $location;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date;

    #[ORM\Column(type: "string", length: 255)]
    private string $category;

    #[ORM\Column(type: "float")]
    private float $price;

        #[ORM\ManyToOne(targetEntity: Organizer::class, inversedBy: "events")]
    #[ORM\JoinColumn(name: 'idOrganizer', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Organizer $idOrganizer;

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

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($value)
    {
        $this->description = $value;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function setLocation($value)
    {
        $this->location = $value;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($value)
    {
        $this->date = $value;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setCategory($value)
    {
        $this->category = $value;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice($value)
    {
        $this->price = $value;
    }

    public function getIdOrganizer()
    {
        return $this->idOrganizer;
    }

    public function setIdOrganizer($value)
    {
        $this->idOrganizer = $value;
    }
}
