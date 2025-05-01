<?php

namespace App\Entity;

use App\Entity\Feedback;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;

#[ORM\Entity]
#[Vich\Uploadable]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s'-]+$/u",
        message: "First name must contain only letters."
    )]
    #[Assert\NotBlank(message: "First name is required.")]
    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $firstName = null;    

    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s'-]+$/u",
        message: "Last name must contain only letters."
    )]
    #[Assert\NotBlank(message: "Last name is required.")]
    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $lastName = null;
    
    #[Assert\NotBlank(message: "Gender is required.")]
    #[ORM\Column(type: "string", nullable: true)]
    private ?string $gender = null;

    #[Assert\NotBlank(message: "Date of birth is required.")]
    #[Assert\LessThan("today", message: "Date of birth must be in the past.")]
    #[ORM\Column(type: "date", nullable: true)]
    private ?\DateTimeInterface $dateOfBirth = null;

    #[Assert\NotBlank(message: "Email is required.")]
    #[Assert\Email(message: "Invalid email format.")]
    #[ORM\Column(type: "string", length: 255, unique: true, nullable: true)]
    private ?string $email = null;

    #[Assert\NotBlank(message: "Phone number is required.")]
    #[Assert\Regex(
        pattern: "/^\d{6,15}$/",
        message: "Phone number must contain only digits (6-15 digits)."
    )]
    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $phoneNumber = null;
    
    #[Assert\NotBlank(message: "Country is required.")]
    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $country = null;

    #[Vich\UploadableField(mapping: 'profile_pictures', fileNameProperty: 'profilePictureUrl')]
    private ?File $profilePictureFile = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $profilePictureUrl = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: "string", length: 1000)]
    private string $password;

    #[ORM\Column(type: "json")]
    private array $roles = [];

    #[ORM\OneToMany(mappedBy: "userId", targetEntity: Feedback::class, cascade: ["remove"])]
    private Collection $feedbacks;

    #[ORM\Column(type: "boolean")]
    private bool $isBanned = false;

    /**
     * @var Collection<int, Trip>
     */
    #[ORM\OneToMany(targetEntity: Trip::class, mappedBy: 'user')]
    private Collection $trips;


    public function __construct()
    {
        $this->feedbacks = new ArrayCollection();
        $this->trips = new ArrayCollection();
    }

    public function getProfilePictureFile(): ?File
    {
        return $this->profilePictureFile;
    }

    public function setProfilePictureFile(?File $image = null): void
    {
        $this->profilePictureFile = $image;

        if ($image !== null) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getProfilePictureUrl(): ?string
    {
        return $this->profilePictureUrl;
    }

    public function setProfilePictureUrl(?string $profilePictureUrl): void
    {
        $this->profilePictureUrl = $profilePictureUrl;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }
    
    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }
    
    public function getLastName(): ?string
    {
        return $this->lastName;
    }
    
    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }
    
    public function getGender(): ?string
    {
        return $this->gender;
    }
    
    public function setGender(?string $gender): void
    {
        $this->gender = $gender;
    }

    public function getDateOfBirth(): ?\DateTimeInterface
    {
        return $this->dateOfBirth;
    }
    
    public function setDateOfBirth(?\DateTimeInterface $dateOfBirth): void
    {
        $this->dateOfBirth = $dateOfBirth;
    }    
    
    public function getEmail(): ?string
    {
        return $this->email;
    }
    
    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }
    
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }
    
    public function setPhoneNumber(?string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }
    
    public function getCountry(): ?string
    {
        return $this->country;
    }
    
    public function setCountry(?string $country): void
    {
        $this->country = $country;
    }    

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {

    }

    public function getFeedbacks(): Collection
    {
        return $this->feedbacks;
    }

    public function addFeedback(Feedback $feedback): self
    {
        if (!$this->feedbacks->contains($feedback)) {
            $this->feedbacks[] = $feedback;
            $feedback->setUserId($this);
        }
        return $this;
    }

    public function removeFeedback(Feedback $feedback): self
    {
        if ($this->feedbacks->removeElement($feedback)) {
            if ($feedback->getUserId() === $this) {
                $feedback->setUserId(null);
            }
        }
        return $this;
    }

    public function isBanned(): bool
    {
        return $this->isBanned;
    }

    public function setIsBanned(bool $isBanned): void
    {
        $this->isBanned = $isBanned;
    }

    /**
     * @return Collection<int, Trip>
     */
    public function getTrips(): Collection
    {
        return $this->trips;
    }

    public function addTrip(Trip $trip): static
    {
        if (!$this->trips->contains($trip)) {
            $this->trips->add($trip);
            $trip->setUser($this);
        }

        return $this;
    }

    public function removeTrip(Trip $trip): static
    {
        if ($this->trips->removeElement($trip)) {
            // set the owning side to null (unless already changed)
            if ($trip->getUser() === $this) {
                $trip->setUser(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->id ?: $this->getFirstName();
    }
}
