<?php

namespace App\Entity;

use App\Repository\CountryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\CountryRepository::class)]
class Country
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 10)]
    private ?string $phonePrefix = null;

    #[ORM\Column]
    private ?int $phoneNumberLength = null;

    // Getters and setters
    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getPhonePrefix(): ?string { return $this->phonePrefix; }
    public function setPhonePrefix(string $prefix): self { $this->phonePrefix = $prefix; return $this; }
    public function getPhoneNumberLength(): ?int { return $this->phoneNumberLength; }
    public function setPhoneNumberLength(int $length): self { $this->phoneNumberLength = $length; return $this; }
}
