<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private ?int $id = 1; // id fixé à 1 par défaut

    #[ORM\Column(type: 'string', length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(type: 'string', length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateOfBirth = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $profilePictureUrl = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'string')]
    private ?string $role = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    // Autres getters/setters...
    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(string $firstName): self { $this->firstName = $firstName; return $this; }

    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(string $lastName): self { $this->lastName = $lastName; return $this; }

    public function getGender(): ?string { return $this->gender; }
    public function setGender(?string $gender): self { $this->gender = $gender; return $this; }

    public function getDateOfBirth(): ?\DateTimeInterface { return $this->dateOfBirth; }
    public function setDateOfBirth(?\DateTimeInterface $date): self { $this->dateOfBirth = $date; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): self { $this->phone = $phone; return $this; }

    public function getCountry(): ?string { return $this->country; }
    public function setCountry(?string $country): self { $this->country = $country; return $this; }

    public function getProfilePictureUrl(): ?string { return $this->profilePictureUrl; }
    public function setProfilePictureUrl(?string $url): self { $this->profilePictureUrl = $url; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $dateTime): self { $this->createdAt = $dateTime; return $this; }

    public function getRole(): ?string { return $this->role; }
    public function setRole(string $role): self { $this->role = $role; return $this; }
}
